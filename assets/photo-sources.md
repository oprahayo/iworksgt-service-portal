# iWorks Global — licensed illustrative business photographs

These images are selected from **Unsplash free photographs**, *not* from Unsplash+.
All are illustrative and **do not depict iWorks employees, customers, offices, software or work performed for a client**.

Images are fetched through Unsplash's image CDN with width limits and automatic format selection. They are not used as certificates, testimonials, or official CAC imagery.

| Config key | Placement | Creator / source |
| --- | --- | --- |
| consultation | Homepage fallback hero and customer approach | Vitaly Gariev — https://unsplash.com/photos/two-colleagues-reviewing-documents-at-an-office-desk-zQXdDfmkevA |
| registration | CAC registration imagery and relevant blog articles | Kelly Sikkema — https://unsplash.com/photos/person-reviewing-documents-with-calculator-and-laptop-0oZpRxG5Hkk |
| digital | Web development and digital growth section | Bayu Syaits — https://unsplash.com/photos/laptop-and-phone-on-a-desk-with-coding-software-open-oYzjGQ7LCVE |
| tracking | Online progress/working-together photo | The Connected Narrative — https://unsplash.com/photos/a-person-working-on-a-laptop-aEv8ednzJ2E |
| meeting | About-page hero | Unsplash photograph — https://unsplash.com/photos/a-group-of-people-sitting-around-a-white-table-AekHsx1SxXQ |
| strategy | Business support story and trademark service area | Vitaly Gariev — https://unsplash.com/photos/two-business-people-reviewing-documents-together-8k5j5z6ZYT4 |
| developer | About founder fallback (workspace, not founder portrait) | Olumuyiwa Sobowale — https://unsplash.com/photos/a-man-sitting-in-front-of-a-computer-wearing-headphones-kQIdjLbCghA |

Additional FAQ photo: **Pexels photo 8866777**, customer support representative wearing a headset by **Yan Krukau**:
https://www.pexels.com/photo/woman-wearing-headset-smiling-8866777/
Served from Pexels image CDN at constrained 900 px width. Free to use under the Pexels License: https://www.pexels.com/license/
Illustrative stock image, not an iWorks employee or testimonial.

License: https://unsplash.com/license (free commercial use, no attribution required but appreciated).
Please be mindful that public use of identifiable people may still require appropriate releases in specific marketing contexts. No claim of endorsement or association is intended.

Editorial real client work, verified testimonials, team portraits and screenshots should continue to come from the existing iWorks Website Editor rather than stock photos.


## Authentic local Pexels photography — 14 November 2026

The following images are **real photographs by named photographers**, not AI-generated concepts. The photo-only cPanel installer retrieves optimized JPEGs from the original Pexels image CDN, checks JPEG integrity and dimensions, and stores them under `/assets/photography/` on the existing static-asset roots. Exact image pages and source IDs are listed below.

| Installed filename | Photograph / author | Original photo page |
| --- | --- | --- |
| `hero-professional-desktop.jpg` | Black professional using a laptop — Christina Morillo (WOCinTech) | https://www.pexels.com/photo/photography-of-woman-using-laptop-1181727/ |
| `hero-professional-mobile.jpg` | Same photo, downloaded at a smaller resolution | https://www.pexels.com/photo/photography-of-woman-using-laptop-1181727/ |
| `registration-documents.jpg` | Two people doing paperwork — Pavel Danilyuk | https://www.pexels.com/photo/people-doing-paperwork-at-a-desk-8152735/ |
| `trademark-branding.jpg` | Brand strategy documents and design materials — Leeloo The First | https://www.pexels.com/photo/branding-company-documents-on-desk-7598022/ |
| `operations-team.jpg` | Professionals around a laptop — Yan Krukau | https://www.pexels.com/photo/woman-and-colleagues-working-around-laptop-7691696/ |
| `digital-designer.jpg` | Real creative desk work — Tima Miroshnichenko | https://www.pexels.com/photo/person-people-woman-creative-6615292/ |

**License:** https://www.pexels.com/license/ — Pexels free photographs may generally be used on commercial websites without attribution. Recheck third-party rights and any model/brand restrictions for advertising uses. Photographers are credited here for traceability. No photograph represents iWORKS staff, clients, official CAC personnel, clients' projects, or endorsements. The actual founder portrait remains unchanged.

**Selection policy:** A custom photo chosen in the iWORKS CMS stays authoritative. The public PHP views use these locally installed files only where a cover is missing or a known retired `1772909461_e4c73dc4.png` “Paperless” keyboard placeholder is still selected. The three published Business Insights are mapped by their existing article topics: remote operations, trademark, and CAC registration. Catalogue listing, management portraits, testimonials, case studies, portal login, and payment workflows are intentionally unchanged. If a local photo is absent, PHP falls back to the previous image or cover treatment.

**Deployment:** `scripts/deploy_authentic_photography.sh` retrieves these images from their Pexels CDN identifiers, verifies them before publishing, and changes only five public-facing PHP files plus static images. It runs a read-only production safety preflight, creates private rollback archives, and performs public smoke tests. It does not run migrations or modify CMS-selected uploaded images or any customer data.

**Important:** This photographic rollout does not include or use the previously generated AI graphics pack.

## Support section photograph — 14 November 2026

- **Placement:** homepage “Support that stays with you” photograph.
- **Source:** Jep Gambardella, authentic stock photo of a Black woman wearing a headset, using a laptop in an office. https://www.pexels.com/photo/shallow-focus-of-a-woman-working-in-the-office-7689752/
- **Local file:** `/assets/photography/support-agent-black.jpg`, downloaded from `https://images.pexels.com/photos/7689752/pexels-photo-7689752.jpeg?auto=compress&cs=tinysrgb&w=1000` by the release installer.
- **License:** https://www.pexels.com/license/ — free commercial use subject to licensing and third-party/likeness restrictions.
- **Disclaimer:** The photograph is illustrative; the subject is not presented as an iWORKS employee or customer. The existing image remains a fallback if the local file has not yet been installed.

### Published client logo strip and case studies

The homepage hero logo strip draws from `iworks_case_studies` entries with status `published`, and `iworks_testimonials` entries with status `published` and `consent_confirmed=1`. It displays a brand only when a logo and brand name are present. Images are never converted destructively; grayscale is a CSS filter that returns to the original color on hover. Logos are deliberately non-clickable, and animation stops for reduced-motion preferences. Case study teasers are published CMS records only. Unpublished or absent case studies result in an honest preparation notice, not invented work.
