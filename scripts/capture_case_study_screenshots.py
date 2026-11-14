#!/usr/bin/env python3
"""Capture honest first-viewport homepage screenshots for iWORKS client projects.

Never fabricates a screenshot. Non-responsive websites are skipped, not replaced
with lookalike graphics. Run in an isolated GitHub Actions runner with browser.
"""
from __future__ import annotations
import io
import json
import re
from datetime import datetime, timezone
from pathlib import Path
from PIL import Image
from playwright.sync_api import sync_playwright

DEST = Path("assets/branding/case-studies")
SITES = {
    "amandla-institute-formation-hosting": [
        "https://amandlainstitute.org/",
    ],
    "triply-travels-company-formation-travel-website-portals": [
        "https://triplytravels.com/index.html",
    ],
    "restoration-llc-healthcare-website-design-hosting-management": [
        "https://www.restorationllc.net/",
        "https://restorationllc.net/",
    ],
    "karis-health-services-digital-healthcare-presence": [
        "https://karishealthservices.org/",
    ],
    "cornerstonetl-senior-care-website-design-hosting": [
        "https://cornerstonetlllc.com/",
        "https://www.cornerstonetlllc.com/",
    ],
}
DISALLOWED_TITLES = re.compile(
    r"(?:this site can.t be reached|not found|access denied|just a moment|attention required|error 403)",
    re.I,
)

def main() -> None:
    DEST.mkdir(parents=True, exist_ok=True)
    manifest = {}
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, args=["--no-sandbox"])
        try:
            for slug, urls in SITES.items():
                done = False
                for url in urls:
                    page = browser.new_page(
                        viewport={"width": 1440, "height": 900},
                        device_scale_factor=1,
                        locale="en-US",
                    )
                    try:
                        response = page.goto(url, wait_until="domcontentloaded", timeout=35000)
                        if not response or response.status >= 400:
                            raise RuntimeError(f"unexpected HTTP status: {response.status if response else 'none'}")
                        page.wait_for_timeout(1800)
                        # Remove an obstructive consent banner via the site's
                        # real UI in this fresh, isolated capture session.
                        # Prefer privacy-preserving rejection where offered.
                        for consent in ("Reject all", "Only necessary", "Decline all", "Accept all"):
                            button = page.get_by_role("button", name=re.compile(r"^" + re.escape(consent) + r"$", re.I)).first
                            try:
                                if button.count() and button.is_visible():
                                    button.click(timeout=2500)
                                    page.wait_for_timeout(450)
                                    break
                            except Exception:
                                pass
                        # Browsers may retain an accidental text selection
                        # from a consent click or an autoplaying slider.
                        # Clear that UI state so previews never show blue
                        # highlighted text, selected elements or focus rings.
                        page.evaluate("""() => {
                            const selection = window.getSelection();
                            if (selection) selection.removeAllRanges();
                            if (document.activeElement instanceof HTMLElement) {
                                document.activeElement.blur();
                            }
                        }""")
                        page.wait_for_timeout(250)
                        title = page.title().strip()
                        visible = page.locator("body").inner_text(timeout=10000).strip()
                        if DISALLOWED_TITLES.search(title) or len(visible) < 100:
                            raise RuntimeError("browser did not render a meaningful live client homepage")
                        actual_url = page.url
                        if not actual_url.startswith("https://"):
                            raise RuntimeError("site unexpectedly left HTTPS")
                        raw_png = page.screenshot(type="png", full_page=False, animations="disabled")
                        with Image.open(io.BytesIO(raw_png)) as img:
                            out = DEST / f"{slug}.webp"
                            img.convert("RGB").save(out, "WEBP", quality=78, method=6)
                        manifest[slug] = {
                            "requested": url,
                            "captured_url": actual_url,
                            "page_title": title[:200],
                            "captured_at_utc": datetime.now(timezone.utc).isoformat(),
                            "http_status": response.status,
                            "source": "real Chromium screenshot, first viewport, 1440x900",
                        }
                        print(f"CAPTURED {slug} from {actual_url}: {out.stat().st_size} bytes")
                        done = True
                        break
                    except Exception as exc:
                        print(f"SKIPPED TRY {slug} / {url}: {type(exc).__name__}: {str(exc)[:240]}")
                    finally:
                        page.close()
                if not done:
                    print(f"NO GENUINE SCREENSHOT for {slug}; keep CMS cover empty until user-provided screenshot is uploaded")
        finally:
            browser.close()
    (DEST / "capture-manifest.json").write_text(
        json.dumps(manifest, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
    )
    print(f"Captured {len(manifest)} of {len(SITES)} website screenshots")

if __name__ == "__main__":
    main()
