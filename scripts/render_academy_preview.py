#!/usr/bin/env python3
"""Flatten the original approved Academy SVG with repeated NOT VALID watermarks.
No new certificate design: reuse the original artwork, coordinates, fonts and text.
"""
import base64
import io
import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET
from PIL import Image, ImageDraw, ImageFont

NS='{http://www.w3.org/2000/svg}'
XLINK='{http://www.w3.org/1999/xlink}href'
W,H=1447,1087
FONT_MAP={('Lato','400'):'Lato-Regular',('Lato','700'):'Lato-Bold',('Lato','900'):'Lato-Black',('Liberation Serif','700'):'LiberationSerif-Bold'}

def render(source,fonts):
    root=ET.fromstring(Path(source).read_bytes())
    if root.tag!=NS+'svg' or root.get('viewBox')!='0 0 1447 1087':
        raise ValueError('Unsupported original certificate canvas')
    fields=[n for n in root if n.get('id')=='certificate_number']
    if len(fields)!=1 or not re.search(r'\bPREVIEW\b',fields[0].text or ''):
        raise ValueError('Only original unpaid preview permitted')
    im=Image.new('RGB',(W,H),'white')
    def font(name,size):
        return ImageFont.truetype(str(Path(fonts)/(name+'.ttf')),size)
    def text(node):
        name=FONT_MAP.get((node.get('font-family','Lato'),node.get('font-weight','400')))
        if not name or len(node) or not node.text:
            raise ValueError('Unsupported text')
        f=font(name,max(1,round(float(node.attrib['font-size']))))
        anchor={'start':'ls','middle':'ms','end':'rs'}[node.get('text-anchor','start')]
        ImageDraw.Draw(im).text((float(node.attrib['x']),float(node.attrib['y'])),node.text,font=f,anchor=anchor,fill=node.attrib['fill'])
    def qr(node):
        if node.get('data-url')!='https://portal.iworksgt.com/verify':
            raise ValueError('Invalid verification URL')
        match=re.fullmatch(r'translate\((\d+) (\d+)\) scale\(([0-9.]+)\)',node.attrib['transform'])
        if not match or len(node)!=2:
            raise ValueError('Invalid original QR')
        x,y,s=map(float,match.groups())
        rect,path=node
        if rect.tag!=NS+'rect' or path.tag!=NS+'path' or rect.get('width')!='37' or rect.get('height')!='37':
            raise ValueError('Invalid QR structure')
        q=Image.new('RGB',(37,37),'white')
        d=ImageDraw.Draw(q)
        cells=re.findall(r'M(\d+) (\d+)h1v1h-1z',path.attrib['d'])
        if not cells or ' '.join('M%s %sh1v1h-1z'%cell for cell in cells)!=path.attrib['d'].strip():
            raise ValueError('Invalid QR path')
        for a,b in cells:
            a,b=int(a),int(b)
            if not (4<=a<=32 and 4<=b<=32):
                raise ValueError('Invalid QR cell')
            d.point((a,b),fill='black')
        side=round(37*s)
        im.paste(q.resize((side,side),Image.Resampling.NEAREST),(round(x),round(y)))
    for node in root:
        if node.tag==NS+'title':continue
        if node.tag==NS+'image' and node.get('id')=='certificate_background':
            uri=node.attrib[XLINK];prefix='data:image/png;base64,'
            if not uri.startswith(prefix):raise ValueError('Invalid original background')
            with Image.open(io.BytesIO(base64.b64decode(uri[len(prefix):],validate=True))) as art:
                if art.size!=(W,H):raise ValueError('Invalid original background dimensions')
                im.paste(art.convert('RGB'))
        elif node.tag==NS+'g' and node.get('id')=='fixed_heading' and len(node)==1 and node[0].tag==NS+'text':text(node[0])
        elif node.tag==NS+'text':text(node)
        elif node.tag==NS+'g' and node.get('id')=='verification_qr':qr(node)
        else:raise ValueError('Unsupported original certificate element')
    # Cover the full original artwork with staggered diagonal labels.
    size=2100
    marks=Image.new('RGBA',(size,size),(0,0,0,0))
    d=ImageDraw.Draw(marks)
    f=font('Lato-Black',78)
    for row,y in enumerate(range(0,size,190)):
        for x in range(-400+(row%2)*240,size,520):
            d.text((x,y),'NOT VALID',font=f,fill=(146,25,34,112),stroke_width=1)
    marks=marks.rotate(29,resample=Image.Resampling.BICUBIC)
    left=(size-W)//2;top=(size-H)//2
    im=Image.alpha_composite(im.convert('RGBA'),marks.crop((left,top,left+W,top+H))).convert('RGB')
    return im

if __name__=='__main__':
    if len(sys.argv)!=4:raise SystemExit('Usage: render_academy_preview.py INPUT.svg OUTPUT.png FONT_DIR')
    render(sys.argv[1],sys.argv[3]).save(sys.argv[2],format='PNG')
