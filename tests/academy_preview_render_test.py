#!/usr/bin/env python3
"""Exercise real preview pixels using synthetic names, never signed assets."""
import io
import json
import subprocess
import sys
from pathlib import Path
from PIL import Image, ImageChops

renderer=Path(__file__).resolve().parents[1]/"scripts/render_academy_preview.py"
fonts=sys.argv[1]
def run(data):
    return subprocess.run([sys.executable,"-I",str(renderer),fonts],input=json.dumps(data).encode(),capture_output=True)
base={"colour":"purple","student_name":"ACADEMY TEST STUDENT","course_title":"Web Development","duration":"12 Weeks"}
images=[]
for colour in ("orange","purple","green","blue"):
    p=run(dict(base,colour=colour))
    assert p.returncode==0,p.stderr.decode()
    im=Image.open(io.BytesIO(p.stdout))
    assert im.format=="PNG" and im.mode=="RGB" and im.size==(960,721)
    assert not im.info,im.info
    im.load()
    images.append(im)
assert ImageChops.difference(images[0],images[1]).getbbox()
p=run(dict(base,student_name="Élise O'Connor"))
assert p.returncode==0,p.stderr.decode()
assert ImageChops.difference(images[1],Image.open(io.BytesIO(p.stdout))).getbbox()
assert run(dict(base,certificate_number="FORBIDDEN")).returncode!=0
assert run(dict(base,colour="invalid")).returncode!=0
assert run(dict(base,student_name="x"*301)).returncode!=0
print("PASS four restricted RGB raster previews, identity changes and forbidden input rejection")
