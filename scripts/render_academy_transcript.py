#!/usr/bin/env python3
"""Render a private, dated learning transcript, not an issued certificate."""
import json
import sys
from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_RIGHT
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle

def render(data, output, fonts, logo):
    for face in ("Regular","Bold"):
        pdfmetrics.registerFont(TTFont("Lato"+face,str(Path(fonts)/("Lato-"+face+".ttf"))))
    navy=colors.HexColor("#17212e")
    gray=colors.HexColor("#667085")
    orange=colors.HexColor("#e65c00")
    styles={
        "title":ParagraphStyle("title",fontName="LatoBold",fontSize=23,leading=28,textColor=navy),
        "body":ParagraphStyle("body",fontName="LatoRegular",fontSize=10,leading=15,textColor=navy),
        "small":ParagraphStyle("small",fontName="LatoRegular",fontSize=9,leading=13,textColor=gray),
        "head":ParagraphStyle("head",fontName="LatoBold",fontSize=10,leading=14,textColor=colors.white),
        "section":ParagraphStyle("section",fontName="LatoBold",fontSize=13,leading=18,textColor=navy),
    }
    def p(value,style="body"):
        return Paragraph(escape(str(value)),styles[style])
    def table(rows,widths):
        t=Table(rows,colWidths=widths,repeatRows=1,hAlign="LEFT")
        t.setStyle(TableStyle([("BACKGROUND",(0,0),(-1,0),navy),("ROWBACKGROUNDS",(0,1),(-1,-1),[colors.white,colors.HexColor("#f5f6f8")]),("VALIGN",(0,0),(-1,-1),"TOP"),("TOPPADDING",(0,0),(-1,-1),8),("BOTTOMPADDING",(0,0),(-1,-1),8),("LEFTPADDING",(0,0),(-1,-1),8),("RIGHTPADDING",(0,0),(-1,-1),8)]))
        return t
    doc=SimpleDocTemplate(output,pagesize=A4,leftMargin=42,rightMargin=42,topMargin=78,bottomMargin=54,title="Academic transcript - iWorks Global Academy",author="iWorks Global Academy")
    w=A4[0]-84
    story=[p("Academic transcript","title"),Spacer(1,6),p("Dated academic record • Not a certificate","small"),Spacer(1,18),
           p("Student","small"),p(data["student"]),Spacer(1,7),p("Course","small"),p(data["course"]),Spacer(1,7),
           p("Enrollment status: "+data["status"]),p("Training approval: "+data["approval"]),p("Certificate status: "+data["certificate"]),p("Generated: "+data["generated"],"small"),Spacer(1,15),
           p("Learning progress","section"),p(f'{data["completed"]} of {data["total"]} published lessons completed' if data["total"] else "No published lesson records available."),Spacer(1,15),p("Assessment results","section"),Spacer(1,6)]
    rows=[[p(x,"head") for x in ("Assessment","Type","Pass mark","Result")]]
    for r in data["results"]:
        rows.append([p(r["title"]),p(r["kind"]),p(r["pass"]),p(r["result"])])
    if len(rows)>1:story.append(table(rows,[w*.43,w*.15,w*.17,w*.25]))
    else:story.append(p("No published assessment records available."))
    story.extend([Spacer(1,14),p("This transcript records the Academy's current learning records. It is not an independently verifiable credential, does not establish accreditation, and does not replace the separately issued certificate.","small")])
    def page(c,d):
        c.saveState()
        c.setFillColor(orange);c.rect(42,A4[1]-39,w,3,fill=1,stroke=0)
        c.drawImage(ImageReader(logo),42,A4[1]-70,width=235,height=24,preserveAspectRatio=True,anchor="w",mask="auto")
        c.setFont("LatoRegular",9);c.setFillColor(gray);c.drawRightString(A4[0]-42,A4[1]-62,"ACADEMY")
        c.setStrokeColor(colors.HexColor("#e1e5eb"));c.line(42,42,A4[0]-42,42)
        c.drawString(42,28,"© iWorks Global");c.drawRightString(A4[0]-42,28,f"Page {d.page}")
        c.restoreState()
    doc.build(story,onFirstPage=page,onLaterPages=page)

if __name__=="__main__":
    if len(sys.argv)!=5:raise SystemExit("Usage: render_academy_transcript.py INPUT.json OUTPUT.pdf FONT_DIR LOGO.png")
    data=json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    render(data,sys.argv[2],sys.argv[3],sys.argv[4])
