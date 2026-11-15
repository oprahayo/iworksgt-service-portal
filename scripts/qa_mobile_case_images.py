#!/usr/bin/env python3
"""Read-only browser QA for real iWORKS Case Studies images/navigation.

Collects response status, image naturalWidth, computed visibility and screenshots.
Never authenticates or submits forms.
"""
import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

OUT=Path("case-mobile-qa")
OUT.mkdir(exist_ok=True)
CASES=[
    ("live", "https://iworksgt.com/"),
    ("fixture", "http://127.0.0.1:8765/home-case-preview.html"),
]

def inspect(page, name):
    html=page.content()
    img=page.locator(".iw-home-case-card img")
    rail=page.locator("[data-iw-showcase-track]")
    report={
        "name":name, "url":page.url,
        "has_showcase": rail.count()>0,
        "has_old_selected_work": "Real projects. Real progress." in html,
        "has_pricing_services": "Pricing &amp; services" in html,
        "images": [],
        "body_overflow_px":page.evaluate("document.documentElement.scrollWidth-window.innerWidth"),
    }
    if img.count():
        report["first_image_ancestor_layout"]=img.first.evaluate("""image => {
          const nodes=[], labels=['IMG','.iw-home-case-window','.iw-home-case-cover',
            '.iw-home-case-visual','.iw-home-case-card','.iw-home-case-grid',
            '.iw-home-case-showcase','.iw-home-case-stories'];
          for (let el=image;el&&nodes.length<11;el=el.parentElement) {
            const r=el.getBoundingClientRect(), s=getComputedStyle(el);
            nodes.push({tag:el.tagName,classes:el.className,
              width:r.width,height:r.height,x:r.x,y:r.y,
              display:s.display,position:s.position,
              aspectRatio:s.aspectRatio,overflow:s.overflow,
              flex:s.flex,gridTemplateColumns:s.gridTemplateColumns,
              minWidth:s.minWidth,widthCss:s.width,heightCss:s.height});
          }
          return nodes;
        }""")
    for i in range(min(img.count(),5)):
        report["images"].append(img.nth(i).evaluate("""image => {
            const s=getComputedStyle(image), r=image.getBoundingClientRect();
            const parent=image.closest('.iw-home-case-visual');
            const p=parent?parent.getBoundingClientRect():null;
            return {
                src:image.currentSrc||image.src, complete:image.complete,
                naturalWidth:image.naturalWidth, naturalHeight:image.naturalHeight,
                width:r.width,height:r.height,display:s.display,visibility:s.visibility,
                opacity:s.opacity,objectFit:s.objectFit,loading:image.loading,
                viewportLeft:r.left, parentHeight:p?p.height:null,
            };
        }"""))
    return report

def main():
    failures=[]
    results=[]
    verify_live=os.environ.get("IWORKS_VERIFY_LIVE", "0") == "1"
    with sync_playwright() as p:
        for engine in ("chromium","webkit"):
            browser=getattr(p, engine).launch(headless=True)
            for platform, url in CASES:
                page=browser.new_page(viewport={"width":390,"height":844}, device_scale_factor=1,
                                         is_mobile=True,has_touch=True)
                name=f"{platform}-{engine}-mobile"
                bad_responses=[]
                page.on("response", lambda resp: bad_responses.append({
                    "url":resp.url, "status":resp.status
                }) if "case-studies" in resp.url and resp.status>=400 else None)
                try:
                    resp=page.goto(url,wait_until="domcontentloaded",timeout=35000)
                    page.wait_for_timeout(1350)
                    cases=page.locator(".iw-home-case-stories")
                    if cases.count():
                        cases.scroll_into_view_if_needed()
                        page.wait_for_timeout(900)
                        page.locator(".iw-home-case-stories img").evaluate_all(
                            "(imgs)=>imgs.forEach(el=>el.loading='eager')")
                        page.wait_for_timeout(1100)
                    result=inspect(page,name)
                    result["http_status"]=resp.status if resp else 0
                    result["bad_responses"]=bad_responses
                    print(json.dumps(result,indent=2))
                    page.screenshot(path=str(OUT/f"{name}.png"),full_page=True,animations="disabled")
                    if platform=="fixture" or (platform=="live" and verify_live):
                        if not result["has_showcase"]:failures.append(f"{name}: carousel missing")
                        if platform=="live" and result["has_pricing_services"]:
                            failures.append(f"{name}: redundant Pricing & services still in public menu")
                        if not result["images"]:failures.append(f"{name}: no image tags")
                        for photo in result["images"]:
                            if not photo["naturalWidth"] or photo["width"]<180 or photo["height"]<120:
                                failures.append(f"{name}: screenshot not rendered {photo}")
                        if result["body_overflow_px"]>3:failures.append(f"{name}: horizontal document overflow")
                        if result["has_pricing_services"]:failures.append(f"{name}: duplicate Pricing & services still in menu")
                    results.append(result)
                except Exception as exc:
                    failures.append(f"{name}: {exc}")
                    print(f"QA ERROR {name}: {exc}")
                finally:
                    page.close()
            browser.close()
    (OUT/"report.json").write_text(json.dumps(results,indent=2))
    if failures:
        print("FAILED: "+"; ".join(failures))
        raise SystemExit(1)
    print("Mobile case-study image QA PASS." + (" Live production verified." if verify_live else " Source preview verified; live is diagnostic only."))

if __name__=="__main__": main()
