#!/usr/bin/env python3
"""Render a restricted Academy proof; never load signed artwork or identifiers."""
import json
import sys
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

W, H = 960, 721
COLORS = {"orange":"#c95712", "purple":"#683c9d", "green":"#217650", "blue":"#2368a0"}

def render(data, fonts):
    if set(data) != {"colour", "student_name", "course_title", "duration"}:
        raise ValueError("Unexpected preview fields")
    if any(not isinstance(v, str) or not v or len(v) > 300 for v in data.values()):
        raise ValueError("Invalid preview fields")
    accent = COLORS[data["colour"]]
    im = Image.new("RGB", (W,H), "#f9f7f1")
    d = ImageDraw.Draw(im)
    def font(size, weight="Regular"):
        return ImageFont.truetype(str(Path(fonts)/("Lato-"+weight+".ttf")),size)
    def center(y, value, size, color="#222a35", weight="Regular", width=800):
        f=font(size,weight)
        while d.textlength(value,font=f)>width and size>12:
            size-=1
            f=font(size,weight)
        if d.textlength(value,font=f)>width:
            raise ValueError("Preview field too long")
        d.text((W/2,y),value,font=f,fill=color,anchor="mm")
    d.rectangle((12,12,W-13,H-13),outline=accent,width=8)
    d.rectangle((32,32,W-33,H-33),outline=accent,width=2)
    d.rectangle((32,32,W-33,100),fill=accent)
    center(65,"UNPAID PREVIEW — NOT A CERTIFICATE",26,"#ffffff","Black")
    center(145,"iWORKS GLOBAL ACADEMY",29,accent,"Bold")
    center(205,"CERTIFICATE DESIGN PREVIEW",35,"#252c38","Black")
    center(260,"Review your details before payment",22)
    center(335,data["student_name"].upper(),48,"#222a35","Black")
    d.line((120,375,W-120,375),fill=accent,width=2)
    center(411,data["course_title"],29,"#222a35","Bold")
    center(455,"Training duration: "+data["duration"],23)
    d.rectangle((150,510,810,570),outline=accent,width=2)
    center(540,"CERTIFICATE NUMBER ISSUED AFTER PAYMENT",21,accent,"Bold")
    center(600,"No verification code, signature or seal",19)
    d.rectangle((32,645,W-33,H-33),fill=accent)
    center(666,"NOT VALID FOR EMPLOYMENT OR VERIFICATION",19,"#ffffff","Black")
    # Burn overlapping repeated warnings into the pixels, not HTML or metadata.
    overlay=Image.new("RGBA",(W,H),(0,0,0,0))
    mark=Image.new("RGBA",(1250,1050),(0,0,0,0))
    md=ImageDraw.Draw(mark)
    for y in range(0,1050,135):
        for x in range(-130,1250,520):
            md.text((x,y),"NOT VALID • UNPAID",font=font(35,"Black"),fill=(133,22,32,82))
    mark=mark.rotate(26,resample=Image.Resampling.BICUBIC,expand=False)
    overlay.alpha_composite(mark.crop((145,164,1105,885)))
    central=Image.new("RGBA",(W,H),(0,0,0,0))
    ImageDraw.Draw(central).text((W/2,H/2),"PREVIEW ONLY",font=font(100,"Black"),fill=(145,18,29,158),anchor="mm")
    overlay=Image.alpha_composite(overlay,central.rotate(26,resample=Image.Resampling.BICUBIC))
    return Image.alpha_composite(im.convert("RGBA"),overlay).convert("RGB")

if __name__ == "__main__":
    if len(sys.argv)!=2:
        raise SystemExit("Usage: render_academy_preview.py FONT_DIR")
    raw=sys.stdin.buffer.read(16385)
    if len(raw)>16384:
        raise ValueError("Preview request too large")
    render(json.loads(raw),sys.argv[1]).save(sys.stdout.buffer,format="PNG")
