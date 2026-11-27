#!/usr/bin/env python3
"""Render the fixed Academy SVG subset to PDF without Inkscape or Cairo."""
import base64
import io
import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import HexColor
from reportlab.lib.utils import ImageReader

NS = '{http://www.w3.org/2000/svg}'
XLINK = '{http://www.w3.org/1999/xlink}href'
W, H = 1447, 1087
FONT_FILES = {'Lato-Regular':'Lato-Regular.ttf', 'Lato-Bold':'Lato-Bold.ttf', 'Lato-Black':'Lato-Black.ttf', 'LiberationSerif-Bold':'LiberationSerif-Bold.ttf'}
FONT_MAP = {('Lato','400'):'Lato-Regular', ('Lato','700'):'Lato-Bold', ('Lato','900'):'Lato-Black', ('Liberation Serif','700'):'LiberationSerif-Bold'}
LIMITS = {'certificate_number':(520,16), 'student_full_name':(1150,16), 'course_title':(1120,16), 'duration':(800,16), 'issue_date':(800,16)}

def render(source, output, fonts):
    root = ET.fromstring(Path(source).read_bytes())
    if root.tag != NS+'svg' or root.get('viewBox') != '0 0 1447 1087':
        raise ValueError('Unsupported certificate canvas')
    for name, file in FONT_FILES.items():
        pdfmetrics.registerFont(TTFont(name, str(Path(fonts)/file)))
    c = canvas.Canvas(str(output), pagesize=(W*.75,H*.75), pageCompression=1)
    c.scale(.75,.75)
    def text(node):
        value = node.text or ''
        if len(node) or not value: raise ValueError('Unsupported text element')
        font = FONT_MAP.get((node.get('font-family','Lato'),node.get('font-weight','400')))
        if not font: raise ValueError('Unsupported certificate font')
        size = float(node.attrib['font-size']); x=float(node.attrib['x']); y=float(node.attrib['y'])
        if node.get('id') in LIMITS:
            limit, minimum = LIMITS[node.get('id')]
            width=pdfmetrics.stringWidth(value,font,size)
            if width > limit: size *= limit/width
            if size < minimum: raise ValueError('Certificate text cannot fit; use a shorter approved display name or course label')
        c.setFont(font,size); c.setFillColor(HexColor(node.attrib['fill']))
        anchor = node.get('text-anchor','start')
        draw={'start':c.drawString,'middle':c.drawCentredString,'end':c.drawRightString}[anchor]
        draw(x,H-y,value)
    def qr(node):
        if node.get('data-url')!='https://portal.iworksgt.com/verify': raise ValueError('Invalid verification URL')
        match=re.fullmatch(r'translate\((\d+) (\d+)\) scale\(([0-9.]+)\)',node.attrib['transform'])
        if not match or len(node)!=2: raise ValueError('Unsupported QR')
        x,y,s=map(float,match.groups())
        rect,path=node
        if rect.tag!=NS+'rect' or path.tag!=NS+'path' or rect.get('width')!='37' or rect.get('height')!='37': raise ValueError('Invalid QR structure')
        c.setFillColor(HexColor('#ffffff'));c.rect(x,H-y-37*s,37*s,37*s,stroke=0,fill=1)
        c.setFillColor(HexColor('#000000'))
        cells=re.findall(r'M(\d+) (\d+)h1v1h-1z',path.attrib['d'])
        if not cells or ' '.join('M%s %sh1v1h-1z'%cell for cell in cells)!=path.attrib['d'].strip(): raise ValueError('Unsupported QR path')
        for a,b in cells:
            a,b=int(a),int(b)
            if not (4<=a<=32 and 4<=b<=32):raise ValueError('QR quiet zone compromised')
            c.rect(x+a*s,H-y-(b+1)*s,s,s,stroke=0,fill=1)
    for node in root:
        if node.tag==NS+'title':continue
        if node.tag==NS+'image' and node.get('id')=='certificate_background':
            uri=node.attrib[XLINK];prefix='data:image/png;base64,'
            if not uri.startswith(prefix):raise ValueError('Invalid background')
            raw=base64.b64decode(uri[len(prefix):],validate=True)
            image=ImageReader(io.BytesIO(raw))
            if image.getSize()!=(W,H):raise ValueError('Invalid background dimensions')
            c.drawImage(image,0,0,width=W,height=H)
        elif node.tag==NS+'g' and node.get('id')=='fixed_heading' and len(node)==1 and node[0].tag==NS+'text':text(node[0])
        elif node.tag==NS+'text':text(node)
        elif node.tag==NS+'g' and node.get('id')=='verification_qr':qr(node)
        else:raise ValueError('Unsupported certificate element')
    c.showPage();c.save()

if __name__=='__main__':
    if len(sys.argv)!=4:raise SystemExit('Usage: render_academy_pdf.py INPUT.svg OUTPUT.pdf FONT_DIR')
    render(*sys.argv[1:])
