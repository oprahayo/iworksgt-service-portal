#!/usr/bin/env python3
"""Capture a genuine Gansell website screenshot and verifiable page metadata.
Never fabricate an image or commit an inaccessible/error page.
"""
import io, json, re
from datetime import datetime,timezone
from pathlib import Path
from urllib.parse import urlparse
from PIL import Image
from playwright.sync_api import sync_playwright

slug="gansell-website-project"
root=Path("assets/branding/case-studies")
root.mkdir(parents=True,exist_ok=True)
urls=["https://gansell.com/","https://www.gansell.com/"]
metadata=None
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True,args=["--no-sandbox"])
    try:
        for url in urls:
            page=browser.new_page(viewport={"width":1440,"height":900},device_scale_factor=1,locale="en-US")
            try:
                response=page.goto(url,wait_until="domcontentloaded",timeout=35000)
                if not response or response.status>=400: raise RuntimeError("HTTP "+str(response.status if response else "none"))
                page.wait_for_timeout(2200)
                title=page.title().strip()
                body=page.locator("body").inner_text(timeout=12000).strip()
                actual=page.url
                hostname=(urlparse(actual).hostname or "").lower()
                if hostname not in ("gansell.com","www.gansell.com"):
                    raise RuntimeError("Website redirected outside Gansell domain: "+hostname)
                if len(body)<65 or re.search(r"Access Denied|Just a moment|Forbidden|Site can't be reached",title,re.I):
                    raise RuntimeError("Rendered website is an error or interstitial")
                page.evaluate("() => {const s=window.getSelection();if(s)s.removeAllRanges();if(document.activeElement instanceof HTMLElement)document.activeElement.blur();}")
                png=page.screenshot(type="png",full_page=False,animations="disabled")
                image=Image.open(io.BytesIO(png)).convert("RGB")
                image.save(root/(slug+".webp"),"WEBP",quality=79,method=6)
                metadata={
                    "requested":url,
                    "captured_url":actual,
                    "page_title":title[:250],
                    "captured_at_utc":datetime.now(timezone.utc).isoformat(),
                    "http_status":response.status,
                    "visible_excerpt":re.sub(r"\s+"," ",body[:900]),
                    "source":"real Chromium screenshot, first viewport, 1440x900",
                }
                print("GANSELL VERIFIED "+json.dumps(metadata))
                break
            except Exception as exc:
                print("GANSELL CANDIDATE FAILED "+url+": "+str(exc)[:350])
            finally:page.close()
    finally:browser.close()
if metadata is None:raise SystemExit("Gansell live screenshot unavailable. Nothing fabricated or published.")
(root/(slug+"-capture.json")).write_text(json.dumps(metadata,indent=2)+"\n",encoding="utf-8")
print("GANSELL IMAGE CREATED "+str(root/(slug+".webp")))
