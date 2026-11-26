#!/usr/bin/env python3
"""Synthetic long transcript: verify actual PDF text and page breaks."""
import json
import subprocess
import sys
import tempfile
from pathlib import Path
from pypdf import PdfReader

root=Path(__file__).resolve().parents[1]
data={"student":"Synthetic Student <Sample>","course":"Web Development","status":"Enrolled","approval":"Not yet approved","certificate":"Not issued","generated":"2026-11-26 12:00 UTC","completed":2,"total":5,"results":[{"title":f"Practical assignment {n} — responsive website and accessibility review","kind":"Assignment","pass":"60%","result":"85% - Passed"} for n in range(1,51)]}
with tempfile.TemporaryDirectory() as td:
    source=Path(td)/"input.json"
    target=Path(td)/"transcript.pdf"
    source.write_text(json.dumps(data))
    subprocess.run([sys.executable,"-I",str(root/"scripts/render_academy_transcript.py"),str(source),str(target),sys.argv[1],str(root/"assets/branding/iworks-social-share.png")],check=True)
    pdf=PdfReader(target)
    assert len(pdf.pages)>1
    text="\n".join(page.extract_text() for page in pdf.pages)
    for phrase in ("Synthetic Student <Sample>","Academic transcript","Not a certificate","2 of 5","85% - Passed","Practical assignment 50","Page 2"):
        assert phrase in text, phrase
    assert text.count("Assessment\n")>=2
print("PASS branded transcript rendering, data, escaped text and pagination")
