<?php
declare(strict_types=1);
// CLI only. Staged privately; never installed in the public document root.
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$data=json_decode(<<<'COURSE_JSON'
{
  "description": "Digital Marketing Foundations — iWorks Academy. Beginner reading course: 8 modules, 16 lessons, 8 quizzes, a timed practice exam and practical campaign project. No paid tools or advertising spend required.",
  "modules": [
    {
      "title": "Marketing foundations",
      "lessons": [
        {
          "title": "The customer journey",
          "body": "Digital marketing uses online channels to reach people, earn trust and encourage a useful action. A customer may discover a business, compare alternatives, buy and later return. These stages are awareness, consideration, conversion and retention. Match each message to its stage. A bakery could use a short video for awareness, a menu page for consideration and WhatsApp for orders. Owned channels include your website and email list; paid channels include advertising; earned reach includes genuine recommendations.\n\nPractice: Choose a business and write one suitable message and channel for each of the four stages. Decide which customer action matters most.",
          "url": "https://academy.hubspot.com/courses/digital-marketing"
        },
        {
          "title": "Audience, goals and offers",
          "body": "Begin with a customer need, not a platform. Define an audience using relevant needs, location and buying behaviour. For a cleaning service, busy households needing weekend appointments are more useful than everyone. A SMART goal is specific, measurable, achievable, relevant and time-bound: for example, 20 qualified enquiries in 30 days. Define a qualified enquiry as someone in your service area requesting a service you offer. A value proposition explains who you help, the outcome and why you are credible. A call to action (CTA) tells people what to do next.\n\nPractice: Write a customer persona, a SMART goal, an offer and one CTA.",
          "url": "https://academy.hubspot.com/courses/digital-marketing"
        }
      ],
      "quiz": [
        {
          "question": "Which outcome best measures a lead-generation goal?",
          "options": [
            "Likes",
            "Qualified enquiries",
            "Font size",
            "Profile views alone"
          ],
          "answer": 1
        },
        {
          "question": "What should come before choosing a platform?",
          "options": [
            "Buying followers",
            "Picking a random budget",
            "Identifying the audience and objective",
            "Posting hourly"
          ],
          "answer": 2
        },
        {
          "question": "Which is an owned channel?",
          "options": [
            "Your website",
            "A stranger's review",
            "A paid ad placement",
            "A competitor's account"
          ],
          "answer": 0
        }
      ]
    },
    {
      "title": "Content and creative tools",
      "lessons": [
        {
          "title": "Content strategy and calendars",
          "body": "Content should answer a customer question or make a decision easier. Use three pillars: education, genuine proof and offers. A caption can begin with a relevant hook, explain one benefit and end with one CTA. Reuse ideas across formats instead of copying other creators. An explanation can become a carousel, short video and email. In Google Sheets, create columns for date, platform, topic, format, CTA, owner and status. Include time for responding to customers.\n\nPractice: Plan seven posts for one business, including two educational posts, genuine proof where available and one clear offer. Write a measurable purpose for each.",
          "url": "https://academy.hubspot.com/courses/content-marketing"
        },
        {
          "title": "Canva and CapCut",
          "body": "Canva helps create graphics, carousels and simple brand assets. Select a format, replace sample text, use consistent colours, one heading and enough whitespace. Export the result and check it on your phone. CapCut helps edit short videos: import authorised footage, trim pauses, lead with a useful hook and add readable captions. For vertical social video, start with a 9:16 canvas and keep important text clear of interface overlays. Use licensed music and images. Save an editable copy and a final export.\n\nPractice: Draft one Canva graphic and a 15-second video storyboard with a hook, benefit and CTA. A written storyboard is sufficient.",
          "url": "https://academy.hubspot.com/courses/content-marketing"
        }
      ],
      "quiz": [
        {
          "question": "Which content pillar is useful?",
          "options": [
            "A real customer question",
            "An unrelated hashtag",
            "An identical daily sales pitch",
            "A random image"
          ],
          "answer": 0
        },
        {
          "question": "What makes a mobile video readable?",
          "options": [
            "Tiny text",
            "Clear opening and readable captions",
            "Five CTAs",
            "Unlicensed music"
          ],
          "answer": 1
        },
        {
          "question": "Which calendar fields connect content to results?",
          "options": [
            "File extension",
            "Logo colour",
            "CTA and measurement goal",
            "Device brand"
          ],
          "answer": 2
        }
      ]
    },
    {
      "title": "Social media and community",
      "lessons": [
        {
          "title": "Set up business profiles",
          "body": "Select platforms according to your audience and capacity. Instagram is visual, Facebook can support local communities, LinkedIn suits many professional services and TikTok can support short discovery content. Use a recognisable profile image, consistent name, accurate contact details and a bio explaining the offer. Test your link on a phone as a visitor. Enable two-factor authentication and use individual staff access instead of shared passwords. Meta Business Suite can help manage eligible Facebook and Instagram accounts, messages, scheduled posts and insights; interfaces may change.\n\nPractice: Audit a business profile and draft an improved bio, contact link and three content ideas.",
          "url": "https://business.facebook.com/business/learn/courses"
        },
        {
          "title": "Organic reach and customer care",
          "body": "Organic marketing distributes content without paying for that placement. Useful content, consistency and genuine interaction support discovery. Reach generally counts accounts exposed; impressions count displays, including repeats. Engagement includes actions such as comments, saves and shares. Answer enquiries respectfully and move sensitive information into an approved private channel. WhatsApp Business can organise conversations with labels and quick replies. Do not send unsolicited promotions or share customer photos without permission. Track genuine enquiries rather than purchased followers.\n\nPractice: Write three responses for a pricing enquiry, delivery question and complaint. Use fictional details only.",
          "url": "https://business.facebook.com/business/learn/courses"
        }
      ],
      "quiz": [
        {
          "question": "Which metric counts displays including repeat displays?",
          "options": [
            "Reach",
            "Impressions",
            "Profit",
            "Orders"
          ],
          "answer": 1
        },
        {
          "question": "How should payment details be handled?",
          "options": [
            "Request them in comments",
            "Share in a public sheet",
            "Use an approved secure process",
            "Post screenshots"
          ],
          "answer": 2
        },
        {
          "question": "Which profile feature supports enquiries?",
          "options": [
            "A working contact link",
            "Hidden contact details",
            "Purchased reviews",
            "An unrelated username"
          ],
          "answer": 0
        }
      ]
    },
    {
      "title": "Websites and SEO",
      "lessons": [
        {
          "title": "Search engine optimisation",
          "body": "SEO helps search engines discover and understand useful pages. Search intent means what a person hopes to achieve. Someone searching how to register a business needs guidance; someone searching registration service near me may want a quote. Use a descriptive page title, clear headings, helpful original content, internal links and appropriate image alternative text. Make the page fast and mobile-friendly. A sitemap can assist discovery but cannot guarantee indexing or ranking. Google Search Console helps monitor search visibility and diagnose indexing issues. Avoid buying links or stuffing keywords.\n\nPractice: Write a page title, heading and outline for a local service. Explain its intended search query.",
          "url": "https://developers.google.com/search/docs/fundamentals/seo-starter-guide"
        },
        {
          "title": "Landing pages and local discovery",
          "body": "A landing page should match the promise in the message that brought visitors there. Present a clear benefit, relevant offer, genuine proof and one main CTA. Keep forms short and explain what happens next. Test the page, contact links and confirmation on a narrow phone screen. Eligible businesses can use Google Business Profile to manage their information on Search and Maps. Keep opening hours, service areas, photos and contact information accurate. Ask for genuine reviews without incentives or pressure.\n\nPractice: Sketch six blocks for a mobile landing page: headline, offer, benefits, proof, CTA and privacy or contact information.",
          "url": "https://developers.google.com/search/docs/fundamentals/seo-starter-guide"
        }
      ],
      "quiz": [
        {
          "question": "What is search intent?",
          "options": [
            "The purpose behind a search",
            "Website colour",
            "Logo size",
            "Ad invoice"
          ],
          "answer": 0
        },
        {
          "question": "Which tool diagnoses Google Search indexing?",
          "options": [
            "CapCut",
            "Canva",
            "Google Search Console",
            "Calculator"
          ],
          "answer": 2
        },
        {
          "question": "What should a landing page match?",
          "options": [
            "A competitor's pricing",
            "The referring message's promise",
            "Every possible offer",
            "A random trend"
          ],
          "answer": 1
        }
      ]
    },
    {
      "title": "Email, messaging and CRM",
      "lessons": [
        {
          "title": "Permission-based email marketing",
          "body": "Build an audience who expects your messages through a clear signup. Explain the purpose, follow applicable privacy and marketing requirements and provide a way to unsubscribe. Honour opt-outs; do not buy or scrape lists. An email needs an accurate sender, honest subject, useful message and CTA. Segment by relevant interests or purchase stage. A welcome email can introduce the business; a follow-up may answer a common question. Mailchimp and HubSpot offer campaign tools, with limits that vary by plan. Test emails on mobile and use authorised sending domains.\n\nPractice: Draft a welcome email and a helpful follow-up for a fictional opt-in subscriber.",
          "url": "https://academy.hubspot.com/courses/email-marketing-certification-en"
        },
        {
          "title": "CRM and lead follow-up",
          "body": "A customer relationship management system (CRM) helps organise authorised customer records and follow-up tasks. A small business can start with a protected sheet containing enquiry date, requested service, consent status, stage and next action. Stages could be new, qualified, quoted, won and lost. Follow up when useful; stop promotional contact when requested. Restrict access and remove information you no longer need. Measure response time, qualified leads and completed sales. Email opens are imperfect signals; clicks and completed actions are often more useful.\n\nPractice: Create five fictional enquiries and assign each a stage and responsible next action.",
          "url": "https://academy.hubspot.com/courses/email-marketing-certification-en"
        }
      ],
      "quiz": [
        {
          "question": "What should follow an unsubscribe?",
          "options": [
            "Keep sending",
            "Honour the opt-out",
            "Add another list",
            "Change sender address"
          ],
          "answer": 1
        },
        {
          "question": "What is a CRM for?",
          "options": [
            "Editing videos",
            "Buying followers",
            "Managing customer relationships",
            "Hiding spending"
          ],
          "answer": 2
        },
        {
          "question": "Which is appropriate email practice?",
          "options": [
            "Honest subject and clear CTA",
            "Scraped addresses",
            "Misleading sender",
            "No unsubscribe"
          ],
          "answer": 0
        }
      ]
    },
    {
      "title": "Paid advertising",
      "lessons": [
        {
          "title": "Meta Ads and campaign design",
          "body": "Paid advertising buys distribution. Start with a business objective and select a corresponding campaign objective in Ads Manager. A campaign defines the goal, an ad set typically controls delivery choices and an ad contains creative and destination. Exact settings vary. Set a spend ceiling, suitable audience, clear offer and tracking plan. Compare two creative variations by changing one main variable at a time. Check the landing page before launching. A boosted post can be simpler; Ads Manager offers more controls. Neither guarantees results.\n\nPractice: Plan a mock seven-day enquiry campaign with an audience, budget ceiling, two creative ideas and stop rule. Do not activate an ad.",
          "url": "https://skillshop.withgoogle.com/googleads/"
        },
        {
          "title": "Google Search Ads",
          "body": "Google Search ads can reach people actively searching for a service. Group related keywords, write relevant ad text and link to a matching landing page. Keyword match types help determine eligible searches; negative keywords help exclude irrelevant searches. Set a budget and bidding approach aligned with your goal. Review search terms and meaningful conversions instead of assuming every click is valuable. A shoe-repair business may exclude searches for free repair tutorials if they do not match its service.\n\nPractice: Draft five keywords, three negative keywords, an ad headline and landing-page CTA for a fictional service. No spending is required.",
          "url": "https://skillshop.withgoogle.com/googleads/"
        }
      ],
      "quiz": [
        {
          "question": "What comes before advertising spend?",
          "options": [
            "Objective and budget",
            "Celebrity endorsement",
            "Unlimited spend",
            "Random target"
          ],
          "answer": 0
        },
        {
          "question": "What do negative keywords help do?",
          "options": [
            "Increase logo size",
            "Exclude irrelevant searches",
            "Buy emails",
            "Disable measurement"
          ],
          "answer": 1
        },
        {
          "question": "What best measures lead campaign success?",
          "options": [
            "Ad colour",
            "Accounts created",
            "Qualified enquiries at acceptable cost",
            "Any comment"
          ],
          "answer": 2
        }
      ]
    },
    {
      "title": "Analytics and optimisation",
      "lessons": [
        {
          "title": "Marketing metrics",
          "body": "CTR is clicks divided by impressions times 100. CPC is ad spend divided by clicks. Conversion rate is conversions divided by the selected denominator, such as visits, times 100. CPA is spend divided by acquisitions. ROAS is attributed ad revenue divided by ad spend; it is not profit. Example: ₦10,000 spend, 5,000 impressions, 100 clicks and five purchases worth ₦30,000 gives CTR 2%, CPC ₦100, CPA ₦2,000 and ROAS 3x. Other business costs still matter.\n\nPractice: Calculate CTR, CPC and CPA for ₦6,000 spend, 3,000 impressions, 60 clicks and three leads. Answers: 2%, ₦100 and ₦2,000.",
          "url": "https://support.google.com/analytics/answer/10917952"
        },
        {
          "title": "GA4, campaign tags and reporting",
          "body": "Google Analytics 4 measures website and app interactions as events. A key event represents an important action such as a completed enquiry form. Verify tracking and honour applicable consent requirements. UTM tags identify campaign traffic: utm_source is where it came from, utm_medium is channel type and utm_campaign names the promotion. Use consistent names, for example https://example.com/?utm_source=instagram&utm_medium=social&utm_campaign=november_offer. Never include personal information in URLs. Search Console measures search visibility; GA4 measures site behaviour. Reports can differ.\n\nPractice: Write a sample tagged link and a short report describing results, a possible explanation and a next test.",
          "url": "https://support.google.com/analytics/answer/10917952"
        }
      ],
      "quiz": [
        {
          "question": "What is CTR for 50 clicks from 2,000 impressions?",
          "options": [
            "0.25%",
            "25%",
            "2.5%",
            "40%"
          ],
          "answer": 2
        },
        {
          "question": "What identifies the referring source in a UTM link?",
          "options": [
            "utm_source",
            "Password",
            "Profit",
            "Invoice number"
          ],
          "answer": 0
        },
        {
          "question": "What is ROAS?",
          "options": [
            "Revenue minus salaries",
            "Ad revenue divided by ad spend",
            "Clicks divided by impressions",
            "Guaranteed profit"
          ],
          "answer": 1
        }
      ]
    },
    {
      "title": "Campaign planning and ethics",
      "lessons": [
        {
          "title": "Build a 30-day plan",
          "body": "Week 1: audit the business, define a measurable goal and audience, and improve its profile and landing page. Week 2: publish helpful content and test the enquiry journey. Week 3: follow up with interested people who have given suitable permission; plan a small paid test only if authorised. Week 4: compare results with the goal, calculate costs and choose an improvement. Choose tools by need: Sheets for planning, Canva for graphics, Meta Business Suite for eligible pages, Search Console for search, GA4 for measurement and a CRM for follow-up.\n\nPractice: Write a four-week plan including responsible owners, a budget ceiling and weekly review.",
          "url": "https://academy.hubspot.com/courses/digital-marketing"
        },
        {
          "title": "Trust and final project",
          "body": "Marketing must be accurate and respectful. Disclose sponsored or affiliate relationships where required, use licensed content and never invent customer reviews. Collect only necessary data for a clear purpose. Avoid spam, deceptive scarcity and unsupported promises. Review relevant rules and platform policies before publishing. For your final project, choose a fictional or authorised business. Prepare an audience profile, SMART goal, offer, landing-page outline, seven-day calendar, sample email, mock ad budget, UTM link and measurement plan. Include a privacy checklist. The timed practice exam tests concepts; the project is separately graded. Certification follows existing Academy review.\n\nPractice: Compile your work into one text submission. No real advertising spend is required.",
          "url": "https://academy.hubspot.com/courses/digital-marketing"
        }
      ],
      "quiz": [
        {
          "question": "How should sponsored content be handled?",
          "options": [
            "Hide sponsorship",
            "Disclose where required",
            "Invent reviews",
            "Collect extra data"
          ],
          "answer": 1
        },
        {
          "question": "Which is a responsible project?",
          "options": [
            "Unapproved real spend",
            "Scraped contact data",
            "Fictional or authorised plan",
            "Fabricated reviews"
          ],
          "answer": 2
        },
        {
          "question": "Why review results weekly?",
          "options": [
            "Make decisions from evidence",
            "Change everything",
            "Hide poor results",
            "Ignore cost"
          ],
          "answer": 0
        }
      ]
    }
  ],
  "exam": [
    {
      "question": "A shop wants 25 qualified enquiries in 30 days. What is this?",
      "options": [
        "A logo guideline",
        "A measurable time-bound goal",
        "A hashtag",
        "A review"
      ],
      "answer": 1
    },
    {
      "question": "Which stage describes a returning buyer?",
      "options": [
        "Awareness",
        "Indexing",
        "Retention",
        "Bidding"
      ],
      "answer": 2
    },
    {
      "question": "Which caption suits a single offer?",
      "options": [
        "Hook, benefit and CTA",
        "Five unrelated offers",
        "Only hashtags",
        "An unverified claim"
      ],
      "answer": 0
    },
    {
      "question": "Which tool creates simple graphics?",
      "options": [
        "Search Console",
        "GA4",
        "Canva",
        "Google Ads"
      ],
      "answer": 2
    },
    {
      "question": "What should be tested on a new profile?",
      "options": [
        "Competitor passwords",
        "Working mobile contact link",
        "Purchased followers",
        "Colour count"
      ],
      "answer": 1
    },
    {
      "question": "How should a complaint be handled?",
      "options": [
        "Publish private details",
        "Ignore all complaints",
        "Buy reviews",
        "Respond respectfully and move sensitive details privately"
      ],
      "answer": 3
    },
    {
      "question": "What does SEO improve?",
      "options": [
        "Useful search visibility",
        "Card processing",
        "Email storage",
        "Video cropping"
      ],
      "answer": 0
    },
    {
      "question": "Which landing page is appropriate for a specific offer?",
      "options": [
        "Unrelated homepage",
        "Matching page with one CTA",
        "Broken link",
        "Admin page"
      ],
      "answer": 1
    },
    {
      "question": "A customer opts out of email. What next?",
      "options": [
        "Send from another list",
        "Sell their details",
        "Stop promotional emails",
        "Add to WhatsApp"
      ],
      "answer": 2
    },
    {
      "question": "Which is a CRM stage?",
      "options": [
        "Video format",
        "Font choice",
        "Campaign source parameter",
        "Qualified enquiry"
      ],
      "answer": 3
    },
    {
      "question": "Before launching an ad, what is needed?",
      "options": [
        "Goal, spend ceiling and tracking",
        "Unlimited spend",
        "Fake reviews",
        "Purchased lists"
      ],
      "answer": 0
    },
    {
      "question": "What do negative keywords exclude?",
      "options": [
        "Accurate reporting",
        "Irrelevant search queries",
        "Every landing page",
        "All customers"
      ],
      "answer": 1
    },
    {
      "question": "₦8,000 spend and 40 clicks gives what CPC?",
      "options": [
        "₦20",
        "₦400",
        "₦200",
        "₦8,040"
      ],
      "answer": 2
    },
    {
      "question": "₦20,000 ad revenue from ₦10,000 spend is which ROAS?",
      "options": [
        "20x",
        "0.5x",
        "10x",
        "2x"
      ],
      "answer": 3
    },
    {
      "question": "Which GA4 event is meaningful for lead generation?",
      "options": [
        "Successful enquiry submission",
        "Accidental scroll only",
        "Logo colour",
        "Staff password"
      ],
      "answer": 0
    },
    {
      "question": "What should a report recommend after a weak campaign?",
      "options": [
        "Fake results",
        "A specific evidence-based test",
        "Never measure",
        "Unapproved spend"
      ],
      "answer": 1
    }
  ]
}
COURSE_JSON
,true,512,JSON_THROW_ON_ERROR);
function dm_check_questions(array $qs,int $count):void{
 if(count($qs)!==$count)throw new RuntimeException('Unexpected question count');
 foreach($qs as $q)if(trim($q['question'])===''||count($q['options'])!==4||count(array_unique($q['options']))!==4||!is_int($q['answer'])||$q['answer']<0||$q['answer']>3)throw new RuntimeException('Invalid question');
}
if(count($data['modules'])!==8)throw new RuntimeException('Expected eight modules');
foreach($data['modules'] as $m){
 if(count($m['lessons'])!==2)throw new RuntimeException('Expected two lessons');
 foreach($m['lessons'] as $l)if(strlen($l['body'])<500||strlen($l['title'])>160||!filter_var($l['url'],FILTER_VALIDATE_URL)||!str_starts_with($l['url'],'https://'))throw new RuntimeException('Incomplete lesson');
 dm_check_questions($m['quiz'],3);
}
dm_check_questions($data['exam'],16);
echo "PASS course payload: 8 modules, 16 lessons, 24 quiz questions, 16 exam questions and one project.\n";
if(in_array('--check',$argv,true))exit(0);
if(count($argv)!==3||$argv[2]!=='--backup-confirmed')throw new RuntimeException('Portal root and verified backup required');
$root=realpath($argv[1]);if(!$root||!is_file($root.'/app/bootstrap.php'))throw new RuntimeException('Missing portal root');
require $root.'/app/bootstrap.php';
$p=db();$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
function dm_stmt(PDO $p,string $sql,array $args=[]):PDOStatement{$s=$p->prepare($sql);$s->execute($args);return $s;}
if((int)$p->query("SELECT GET_LOCK('iworks_dm_sprint9_seed',10)")->fetchColumn()!==1)throw new RuntimeException('Import lock unavailable');
try{
 $p->beginTransaction();
 $cid=(int)dm_stmt($p,"SELECT id FROM academy_courses WHERE slug='digital-marketing' AND active=1 FOR UPDATE")->fetchColumn();
 if(!$cid)throw new RuntimeException('Existing active Digital Marketing catalogue entry missing');
 $meta=dm_stmt($p,'SELECT description,published FROM academy_learning_courses WHERE course_id=? FOR UPDATE',[$cid])->fetch(PDO::FETCH_ASSOC);
 $existing=(int)dm_stmt($p,'SELECT COUNT(*) FROM academy_modules WHERE course_id=?',[$cid])->fetchColumn();
 if($existing){
  $ls=(int)dm_stmt($p,'SELECT COUNT(*) FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id WHERE m.course_id=?',[$cid])->fetchColumn();
  $as=(int)dm_stmt($p,'SELECT COUNT(*) FROM academy_assessments a JOIN academy_lessons l ON l.id=a.lesson_id JOIN academy_modules m ON m.id=l.module_id WHERE m.course_id=?',[$cid])->fetchColumn();
  if($meta&&$meta['description']===$data['description']&&$existing===8&&$ls===16&&$as===10){$p->rollBack();echo "PASS course already imported; no existing records changed.\n";return;}
  throw new RuntimeException('Existing course content detected; refusing overwrite or duplication');
 }
 if($meta&&trim($meta['description'])!==''&&$meta['description']!==$data['description'])throw new RuntimeException('Existing description detected; refusing overwrite');
 if($meta)dm_stmt($p,'UPDATE academy_learning_courses SET description=?,published=1,updated_at=NOW() WHERE course_id=?',[$data['description'],$cid]);
 else dm_stmt($p,'INSERT INTO academy_learning_courses(course_id,description,published,updated_at) VALUES(?,?,1,NOW())',[$cid,$data['description']]);
 $last=0;
 $add=function(int $lesson,string $kind,string $title,string $instructions,array $questions,int $pass,int $minutes)use($p):void{
  dm_stmt($p,'INSERT INTO academy_assessments(lesson_id,kind,title,instructions,questions_json,pass_percent,published,created_at,duration_minutes) VALUES(?,?,?,?,?,?,1,NOW(),?)',[$lesson,$kind,$title,$instructions,json_encode($questions,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$pass,$minutes]);
 };
 foreach($data['modules'] as $i=>$m){
  dm_stmt($p,'INSERT INTO academy_modules(course_id,title,position,published) VALUES(?,?,?,1)',[$cid,$m['title'],($i+1)*10]);$mid=(int)$p->lastInsertId();
  foreach($m['lessons'] as $j=>$l){
   dm_stmt($p,"INSERT INTO academy_lessons(module_id,title,body,video_url,material_url,position,published) VALUES(?,?,?,'',?,?,1)",[$mid,$l['title'],$l['body'],$l['url'],($j+1)*10]);$last=(int)$p->lastInsertId();
  }
  $add($last,'quiz','Module '.($i+1).' knowledge check','Choose one answer per question. Review the lessons before submitting.',$m['quiz'],60,0);
 }
 $add($last,'exam','Digital Marketing timed practice examination','Answer all 16 questions. You have 25 minutes. Submit before the deadline. This is a formative practice examination; certification requires separate administrator review.',$data['exam'],70,25);
 $add($last,'assignment','Practical 30-day campaign project','Choose a fictional or authorised business. Submit a text plan covering: audience and SMART goal; offer and landing-page outline; seven-day content calendar; sample welcome email and mock ad; budget ceiling and stop rule; UTM link; metrics and review plan; privacy and permissions. Rubric: strategy 25, content and channels 25, measurement and budget 25, ethics and clarity 25. No real ad spend or personal data.',[],70,0);
 $p->commit();echo "PASS Digital Marketing published: 16 lessons, 8 quizzes, 1 exam, 1 project.\n";
}catch(Throwable $e){if($p->inTransaction())$p->rollBack();throw $e;}
finally{$p->query("SELECT RELEASE_LOCK('iworks_dm_sprint9_seed')");}
