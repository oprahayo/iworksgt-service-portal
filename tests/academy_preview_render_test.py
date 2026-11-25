#!/usr/bin/env python3
"""Synthetic original-artwork fixture; verify the watermark is burned into pixels."""
import base64
import io
import subprocess
import sys
import tempfile
from pathlib import Path
from PIL import Image, ImageChops

renderer=Path(__file__).resolve().parents[1]/"scripts/render_academy_preview.py"
fonts=sys.argv[1]
art=Image.new("RGB",(1447,1087),(240,248,252))
buf=io.BytesIO()
art.save(buf,format="PNG")
bg=base64.b64encode(buf.getvalue()).decode()
svg=f'''<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1447 1087">
<image id="certificate_background" xlink:href="data:image/png;base64,{bg}"/>
<g id="fixed_heading"><text x="723" y="180" font-size="45" font-family="Lato" font-weight="700" text-anchor="middle" fill="#112233">ORIGINAL TITLE</text></g>
<text id="certificate_number" x="1000" y="900" font-size="25" fill="#112233">PREVIEW</text>
<text id="student_full_name" x="723" y="420" font-size="69" text-anchor="middle" fill="#112233">ACADEMY TEST STUDENT</text>
<text x="723" y="710" text-anchor="middle" font-size="23" fill="#a00000">PREVIEW - NOT VALID</text>
<g id="verification_qr" data-url="https://portal.iworksgt.com/verify" transform="translate(100 900) scale(2)"><rect width="37" height="37"/><path d="M4 4h1v1h-1z"/></g>
</svg>'''
with tempfile.TemporaryDirectory() as td:
    source=Path(td)/"input.svg"
    output=Path(td)/"output.png"
    def run(xml):
        source.write_text(xml)
        return subprocess.run([sys.executable,"-I",str(renderer),str(source),str(output),fonts],capture_output=True)
    p=run(svg)
    assert p.returncode==0,p.stderr.decode()
    with Image.open(output) as im:
        assert im.format=="PNG" and im.mode=="RGB" and im.size==art.size
        assert not im.info, im.info
        # The original background survives, while every quadrant has red watermarks.
        assert (240,248,252) in im.getdata()
        for box in [(0,0,723,543),(723,0,1447,543),(0,543,723,1087),(723,543,1447,1087)]:
            assert any(r>g+25 and r>b+15 for r,g,b in im.crop(box).getdata()),box
        assert ImageChops.difference(im,art).getbbox()
    assert run(svg.replace(">PREVIEW</text>",">IWGA-2026-AAAAAAAAAAAAAAAA</text>")).returncode!=0
print("PASS original artwork raster, repeated watermark coverage and refusal of issued certificate")
