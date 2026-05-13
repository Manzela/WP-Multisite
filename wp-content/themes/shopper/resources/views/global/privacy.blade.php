@extends('layouts.app')

@section('title')
	{{ __('Privacy Policy', 'sage') }} - {!! get_bloginfo('name') !!}
@endsection

@section('content')
	<!-- TODO: @include('partials.page-header') doesn't work here -->
	<!-- TEMPORARY:set up a custom page header -->
	<div class="page-header border-b border-gray-200">
		<h1 class="my-4 text-3xl font-bold leading-none tracking-tight text-gray-900">
			{{ __('Privacy policy', 'woocommerce') }}
		</h1>
	</div>

	@php
		$current_locale = get_locale();
		$is_hebrew = $current_locale === 'he_IL';
	@endphp

	@if($is_hebrew)
		{{-- Hebrew content --}}
		<div class="space-y-4 mt-4">
			<p>עדכון אחרון: 01/07/2025</p>

			<p>דה נקסט ג'נריישן שופר בע"מ ("החברה" או "אנחנו") מכבדת את פרטיות המשתמשים באתר האינטרנט של החברה או בכל אתר אחר
				המנוהל על ידי החברה ובשירותים שהיא מציעה ("האתר"), ומחויבת לשמור על כללי הגנת הפרטיות המקובלים בישראל על פי חוק
				הגנת הפרטיות, התשמ"א -1981, וכן לפי רגולציית ה-GDPR של האיחוד האירופי. מדיניות פרטיות זו מסבירה כיצד אנו אוספים,
				משתמשים, משתפים ושומרים מידע אישי.</p>

			<ol class="list-decimal mr-8 space-y-3">
				<li>מדיניות זו חלה על כלל המשתמשים באתר. עצם השימוש שלך באתר מהווה הסכמה למדיניות פרטיות זו.</li>

				<li>על מנת לספק שירות איכותי ומיטבי למשתמשים, החברה עשויה להשתמש בנתונים האישיים שלך בעת כניסתך לאתר וביצוע
					פעולות בו, ובכלל זאת מידע אישי מזהה שנמסר על ידי המשתמשים או נאסף אודות המשתמשים, מידע על אופן פעילות
					המשתמשים באתר ומידע מפעילות במסכים אישיים שונים (כגון: נייד, מחשב, טאבלט וכיו"ב) (להלן: "המידע הנאסף").</li>

				<li>החברה תעבד מידע אישי רק כאשר יש לכך בסיס חוקי, כגון:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>הסכמתך המפורשת;</li>
						<li>קיום חוזה בינך לבין החברה;</li>
						<li>חובה חוקית החלה על החברה;</li>
						<li>אינטרס לגיטימי של החברה (למשל שיפור שירות, אבטחה, ניתוח נתונים).</li>
					</ul>
				</li>

				<li>המידע שנאסף:
					<br>4.1 מידע שאתה מוסר:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>שם, דוא"ל, מספר טלפון;</li>
						<li>פרטי בית עסק / אמצעי תשלום;</li>
						<li>תוכן שנשלח דרך טפסים או הרשמה.</li>
					</ul>
					<br>4.2 מידע שנאסף באופן אוטומטי, לדוגמה:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>כתובת ה-IP שלך, פרוטוקול האינטרנט, ספק האינטרנט או הדפדפן וסוג המכשיר ממנו אתה גולש;</li>
						<li>הקלטת הפעילות שלך באתר או תרשים העמודים בהם ביקרת;</li>
						<li>מידע שתזין, תשתף או שניתן להשיג מהשימוש שלך באתר.</li>
					</ul>
				</li>

				<li>המידע הנאסף עשוי לשמש את החברה לצרכים עסקיים לגיטימיים בהתאם לחוק וכמפורט במדיניות פרטיות זו.
					<br><br>בנוסף לעיל, המידע משמש את האתר לצרכים הבאים:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>לספק למשתמשים שירותים;</li>
						<li>לשפר את תפעולו התקין של האתר והשירותים;</li>
						<li>לנתח את ולנהל את האתר באופן תקין;</li>
						<li>שליחת עדכונים והודעות;</li>
						<li>ליצירת קשר או לספק למשתמשים נתונים בקשר לאתר או לשירות;</li>
						<li>קיום הוראות הדין;</li>
						<li>מטרות שיווקיות.</li>
					</ul>
				</li>

				<li>החברה מתחייבת לשמור על סודיות הנתונים שנאספים ולהגן עליהם בהתאם לנהלים מחמירים, תוך שימוש בטכנולוגיות
					מתקדמות לאבטחת מידע. יתרה מזאת, החברה מיישמת מדיניות אבטחת מידע מקיפה, הכוללת בקרה על גישה למידע, הצפנה של
					נתונים רגישים ומתן הרשאות מוגבלות לצוותים מורשים בלבד.</li>

				<li>האתר עושה שימוש בקבצי עוגיות (Cookies) (כגון Google Analytics, Hotjar), המסייעים לעקוב אחר הביצועים של
					המשתמשים באתר. השימוש בקבצי עוגיות נעשה בהסכמה של המשתמש, ניתן לחסום אותם בכל עת בהגדרות הדפדפן.</li>

				<li>האתר עושה שימוש באתרי מידע חיצוניים (כדוגמת Google Cloud Storage לאחסון נתונים בענן).</li>

				<li>הינך מסכים כי המידע שנמסר על-ידך ו/או ייאסף אודותיך, יישמר במאגרי המידע של החברה ו/או האתר /או יועבר לשירותי
					ענן של צדדים שלישיים (Google Cloud Storage) לצורך אחסון נתונים.</li>

				<li>האתר נעזר בטכנולוגיות בינה מלאכותית, כלי AI מתקדמים, כלי איסוף מידע וכלי ניתוח אוטומטיים, על מנת לשפר את
					חווית הגלישה של המשתמשים. איסוף המידע כולל:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>מעקב מפורט אחר פעילות המשתמש (צפיות בעמודים, לחיצות, אינטראקציות עם מוצרים);</li>
						<li>מיפוי מסלול הגלישה וניתוח הפעילות;</li>
						<li>מעקב אחר התנהגות חיפוש ועניין במוצרים;</li>
						<li>זיהוי מבקרים באמצעות מזהה ייחודי לכל משתמש;</li>
						<li>מיקום המכשיר בזמן אמת (עם אישור המשתמש);</li>
						<li>נתונים טכניים (IP, דפדפן, מכשיר, זמני כניסה).</li>
					</ul>
					בעת אישור שיתוף מיקום עם האתר, אין אפשרות למחוק את המידע אודות נתוני מיקום המכשיר הנמסרים, אך ניתן לבטל
					שיתוף מיקום עם האתר בהגדרות הדפדפן. מידע זה הוא אנונימי.
				</li>

				<li>במסגרת פעולות שהינך מבצע באתר, כגון ביצוע הזמנה או רכישה, הינך מתבקש למלא פרטים אישיים. פרטים אישיים אלו
					מועברים לבית העסק ממנו בוצעה הרכישה או לחברת סליקה. בית העסק מחויב לנהוג בתום לב במידע המסופק לו על מנת
					לסיים את ההזמנה / רכישה ולא להעבירו לידי צד שלישי שלא לצורך קידום ביצוע ההזמנה / הרכישה ובהתאם לחוק.</li>

				<li>החברה עשויה לשתף את המידע האישי שלך עם צדדים שלישיים, כגון:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>ספקי שירות (אחסון, סליקה, ניתוח נתונים);</li>
						<li>שותפים עסקיים בהתאם לצורך;</li>
						<li>רשויות מוסמכות לפי חוק;</li>
						<li>רוכש או גוף שיתמזג עם החברה.</li>
					</ul>
					אנו מוודאים כי כל צד שלישי ישמור על סודיות ואבטחת המידע בהתאם למדיניות זו.
				</li>

				<li>דוגמאות לפעולות שנותני שירותים עשויים לבצע עם המידע שלך:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>לסייע לנו במתן השירותים;</li>
						<li>לפתח ולתחזק את האתר;</li>
						<li>לצבור מידע על לקוחות ו/או בתי העסק ו/או גולשים ולשפר את שירות הלקוחות, לרבות מידע סטטיסטי. לאחר מכן,
							האתר עשוי לשתף מידע כאמור עם צדדים שלישיים ובלבד שבאמצעות מידע זה לא ניתן להתחקות אחר זהותו של משתמש
							בודד;</li>
						<li>החברה מספקת מידע אישי לגופים וחברות כדי שיעבדו את המידע עבור האתר לפי הוראות האתר ובאופן העולה בקנה
							אחד עם תקנון זה ומדיניות האתר. ככלל, וככל שלא ניתנה הסכמה מראש למסירת מידע אישי, מידע המועבר לצרכים
							אלו אינו כולל פרטים מזהים.</li>
					</ul>
				</li>

				<li>לנותני שירותים ושותפים עסקיים כאמור ניתנת גישה לכל או לחלק מהמידע שלך, והם עשויים להשתמש בעוגיות או
					בטכנולוגיית איסוף אחרות.</li>

				<li>יתכן והמידע יועבר לשרתי אחסון או שירותים מחוץ לגבולות ישראל או האיחוד האירופי (למשל: Google Cloud, AWS).
					במקרים אלו, אנו נסמכים על מנגנוני הגנה חוקיים כגון:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>החלטת נאותות של האיחוד האירופי לישראל;</li>
						<li>סעיפי הגנה חוזיים (Standard Contractual Clauses).</li>
					</ul>
				</li>

				<li>במידה והחברה והאתר ימוזג לתוך פעילות גוף אחר או אם האתר יעבור לבעלות תאגיד אחר ניתן יהיה להעביר לתאגיד החדש
					את המידע הקיים באתר, אבל רק במקרה שהתאגיד יתחייב לשמור על תנאי תקנון זה.</li>

				<li>שימושך באתר מהווה הסכמה לשימוש בטכנולוגיות המתוארות מעלה, הסכמה לאיסוף המידע ושימוש במאגרי המידע למטרות
					החברה ולמטרות שיווקיות ועסקיות. במידה ואינך מסכים, באפשרותך לעדכן את הגדרות מכשירך או הדפדפן שלך ולהמשיך
					להנות מחוויית האתר.</li>

				<li>בהתאם לדין, אתה זכאי:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>לעיין במידע האישי שלך;</li>
						<li>לבקש תיקון מידע שגוי;</li>
						<li>לבקש מחיקה ("הזכות להישכח");</li>
						<li>להגביל עיבוד מידע;</li>
						<li>להעביר מידע לגוף אחר (ניידות);</li>
						<li>להתנגד לעיבוד;</li>
						<li>לבטל הסכמה לעיבוד בכל עת;</li>
						<li>להגיש תלונה לרשות פיקוח.</li>
					</ul>
					ניתן לממש זכויות אלו בעמוד זה, או לפנות אלינו בכתובת: info@example-network.com.
				</li>

				<li>האתר אינו מיועד לקטינים מתחת לגיל 16 ואיננו אוספים מידע אישי מקטינים ביודעין.</li>

				<li>החברה שומרת לעצמה את הזכות לעדכן מדיניות זו מעת לעת. תאריך העדכון האחרון יופיע בראש המסמך. שימוש מתמשך באתר
					מהווה הסכמה לגרסה המעודכנת.</li>

				<li>החברה עושה מאמצים רבים ונעזרת באמצעי אבטחה טכנולוגיים מתקדמים על מנת לאבטח את האתר ואת דרכי הגישה למידע
					הנאסף. אף על פי כן, באבטחת מידע לא ניתן להגנה באופן מוחלט ואין החברה יכולה להתחייב להגנה מלאה של המידע הנאסף
					ותוכן האתר. על כן, החברה לא תישא באחריות ובכל נזק שייגרם למשתמשים עקב השימוש באתר באשר לגורמים שאינם בשליטתה
					המלאה והשימוש באתר הינו באחריות המשתמש.</li>

				<li>להלן מדיניות החברה לשמירת הנתונים:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>נתוני אנליטיקה ומעקב אירועים: 24 חודשים.</li>
						<li>נתוני מיקום: 12 חודשים.</li>
						<li>נתוני הסכמה: 6 חודשים.</li>
						<li>חשבון משתמש: למשך הפעלת החשבון או עד לבקשת מחיקה.</li>
					</ul>
				</li>

				<li>לשאלות או פניות בנוגע למדיניות פרטיות זו:
					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>דה נקסט ג'נריישן שופר בע"מ</li>
						<li>הדובדבן 9, קריית אונו, ישראל</li>
						<li>דוא"ל: info@example-network.com</li>
					</ul>
				</li>
			</ol>
		</div>

	@else
		{{-- English content --}}
		<div class="space-y-4 mt-4">
			<p>Last updated: July 1, 2025</p>

			<p>The Next Generation Shopper Ltd. (the "Company" or "we") respects the privacy of users of its website and any
				other site operated by or service provided by the Company through the internet (the "Website"), and is committed
				to complying with applicable privacy protection standards under Israeli law, including the Protection of Privacy
				Law, 1981, as well as the European Union's GDPR regulation ("Applicable Law"). This Privacy Policy explains how
				we collect, use, share, and store personal information.</p>

			<ol class="list-decimal ml-8 space-y-3">
				<li><strong>Scope of This Policy</strong><br>
					This policy applies to all users of the Website. Your use of the Website constitutes your consent to this
					Privacy Policy.<br><br>
					To provide high-quality services, the Company may use your personal data when you access or interact with
					the Website. This includes identifiable personal information you provide, data collected about you,
					information regarding your activity on the Website, and information from various devices (mobile, desktop,
					tablet, etc.) ("Collected Information").
				</li>

				<li><strong>Legal Basis for Processing</strong><br>
					The Company will only process personal data when there is a legal basis for doing so under Applicable Law,
					such as:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Your explicit consent;</li>
						<li>A contract between you and the Company;</li>
						<li>A legal obligation that applies to the Company;</li>
						<li>The Company's legitimate interest (e.g., service improvement, security, data analysis).</li>
					</ul>
				</li>

				<li><strong>Collected Information</strong><br>
					Collected information includes:<br><br>
					<strong>a. Information you provide:</strong>
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Name, email address, phone number;</li>
						<li>Business details / payment method;</li>
						<li>Content submitted through forms or registration.</li>
					</ul>
					<br><strong>b. Automatically collected information, such as:</strong>
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Your IP address, internet protocol, ISP, browser, and device type;</li>
						<li>Logs of your activity on the site or pages you visited;</li>
						<li>Information you enter, share, or made available through your use of the Website.</li>
					</ul>
				</li>

				<li><strong>Use of Information</strong><br>
					The Collected Information may be used by the Company for legitimate business purposes in accordance with the
					law and as detailed in this policy.<br><br>
					Additionally, the data may be used for:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Providing services;</li>
						<li>Improving the Website and services;</li>
						<li>Analyzing and managing the Website;</li>
						<li>Sending updates and notifications;</li>
						<li>Contacting users or sharing information about the Website or services;</li>
						<li>Compliance with legal obligations;</li>
						<li>Marketing purposes.</li>
					</ul>
					<br>The Company is committed to maintaining the confidentiality of Collected Information and protecting it
					through strict procedures and advanced data security technologies, including access control, encryption, and
					limited authorization only to designated personnel.
				</li>

				<li><strong>Cookies and Tracking Technologies</strong><br>
					The Website uses cookies (e.g., Google Analytics, Hotjar) to track user performance. Cookies are used with
					the user's consent and may be disabled at any time through browser settings.
				</li>

				<li><strong>Sharing of Information</strong><br>
					The Website uses external data services (e.g., Google Cloud Storage for cloud storage). You consent that the
					information you provide and/or collected about you may be stored in the Company's databases and/or
					transferred to third-party cloud services (e.g., Google Cloud Storage, AWS) for storage purposes.<br><br>
					When performing actions on the Website (e.g., placing an order or purchase), you may be required to provide
					personal information. This data may be forwarded to the merchant or payment provider. The merchant is
					obligated to use the information solely to complete the transaction and not to share it with third parties
					beyond what is legally required.<br><br>
					The Company may share your personal data with third parties, such as:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Service providers (e.g., hosting, payment, analytics);</li>
						<li>Business partners as needed;</li>
						<li>Authorities, where legally required;</li>
						<li>A purchaser or successor entity in case of merger or acquisition.</li>
					</ul>
					We ensure all third parties maintain confidentiality and comply with this Privacy Policy.
				</li>

				<li><strong>Data Processing Examples</strong><br>
					Examples of services that may use your data:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Assisting in service provision;</li>
						<li>Website development and maintenance;</li>
						<li>Aggregating data to improve service (anonymized);</li>
						<li>Data processing by authorized parties according to this policy.</li>
					</ul>
					<br>Service providers and business partners may have access to all or part of your data and may use cookies
					or other tracking technologies.
				</li>

				<li><strong>Automation and AI Tools</strong><br>
					The Website uses artificial intelligence technologies, advanced AI tools, and automated data collection and
					analysis tools to improve user experience, including:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Tracking user activity (page views, clicks, product interactions);</li>
						<li>Mapping navigation patterns and behavior analysis;</li>
						<li>Monitoring search behavior and product interest;</li>
						<li>Identifying visitors via unique user identifiers;</li>
						<li>Real-time device location (with user consent);</li>
						<li>Technical data (IP, browser, device, access times).</li>
					</ul>
					Once you enable location sharing with the Website, the location data cannot be deleted, but you can disable
					sharing through your browser settings. This data is anonymized.
				</li>

				<li><strong>International Data Transfers</strong><br>
					Information may be transferred outside of Israel or the EU (e.g., Google Cloud, AWS). In such cases, we rely
					on legal safeguards such as:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>EU adequacy decision for Israel;</li>
						<li>Standard Contractual Clauses (SCCs).</li>
					</ul>
				</li>

				<li><strong>Transfer Due to Merger</strong><br>
					If the Company or Website is merged or transferred, the data may be transferred to the new entity provided
					it agrees to uphold this policy.
				</li>

				<li><strong>Marketing</strong><br>
					Your use of the Website constitutes consent to the described technologies, data collection, and the use of
					the Company's databases for business and marketing purposes. If you do not agree, please update your device
					or browser settings or send us an email with a request to be deleted from that list.
				</li>

				<li><strong>Your Rights</strong><br>
					Under applicable law, you have the right to:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Access your personal data;</li>
						<li>Request correction of inaccurate data;</li>
						<li>Request erasure ("right to be forgotten");</li>
						<li>Restrict data processing;</li>
						<li>Data portability;</li>
						<li>Object to processing;</li>
						<li>Withdraw consent at any time;</li>
						<li>File a complaint with a supervisory authority.</li>
					</ul>
					You can exercise these rights on this page or contact us at: info@example-network.com.
				</li>

				<li><strong>Children's Privacy</strong><br>
					The Website is not intended for users under the age of 16, and we do not knowingly collect personal data
					from minors.
				</li>

				<li><strong>Data Security</strong><br>
					The Company takes extensive measures and uses advanced technologies to protect the Website and the Collected
					Information. However, no security system is completely foolproof, and the Company cannot guarantee full
					protection. Therefore, the Company is not liable for any damage resulting from use of the Website beyond its
					full control, and use of the Website is at the user's own risk.
				</li>

				<li><strong>Changes to This Policy</strong><br>
					The Company reserves the right to update this policy from time to time. The last updated date will be listed
					above. Continued use of the Website constitutes acceptance of the updated policy.
				</li>

				<li><strong>Data Retention Policy</strong>
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>Analytics and event tracking data: 24 months</li>
						<li>Location data: 12 months</li>
						<li>Consent data: 6 months</li>
						<li>User account data: For the duration of the account or until deletion is requested</li>
					</ul>
				</li>
			</ol>

			<div class="mt-6">
				<h3 class="text-lg font-bold mb-2">Contact Us</h3>
				<ul class="list-disc ml-8 space-y-1">
					<li>The Next Generation Shopper Ltd.</li>
					<li>Haduvdevan 9, Kiryat Ono, Israel</li>
					<li>Email: info@example-network.com</li>
				</ul>
			</div>
		</div>
	@endif

	<!-- GDPR User Controls -->
	@php
		$primary_color = get_option('store_settings')['primary_color'] ?? '';
		$text_color = wc_light_or_dark($primary_color, '', 'text-white');
	@endphp
	<div class="mt-8 p-4 border border-gray-300" id="gdpr-controls" dir="{{ $is_hebrew ? 'rtl' : 'ltr' }}">
		<h3 class="text-lg font-medium mb-3">{{ __('Exercise Your Data Rights', 'sage') }}</h3>
		<p class="mb-4 text-gray-700 text-sm">
			{{ __('Manage your personal data in accordance with GDPR using your visitor identifier.', 'sage') }}</p>

		<form id="gdpr-requests-form" class="space-y-3">
			<div>
				<label for="gdpr-visitor-id"
					class="block text-sm font-medium text-gray-800 mb-1">{{ __('Your Visitor ID:', 'sage') }}</label>
				<input type="text" id="gdpr-visitor-id" name="visitor_id" readonly
					class="w-full px-3 py-2 border border-gray-300 bg-gray-100 text-gray-600 text-sm"
					placeholder="{{ __('Loading visitor ID...', 'sage') }}">
				<p class="text-xs text-gray-600 mt-1">
					{{ __('This identifier is automatically detected from your browser storage.', 'sage') }}</p>
			</div>

			<div class="flex flex-wrap gap-2">
				<button type="button" id="gdpr-access"
					class="custom-bg-color-primary hover:opacity-50 {{ $text_color }} px-4 py-2 text-sm">
					{{ __('Download My Data', 'sage') }}
				</button>
				<button type="button" id="gdpr-withdraw"
					class="custom-bg-color-primary hover:opacity-50 {{ $text_color }} px-4 py-2 text-sm">
					{{ __('Withdraw Consent', 'sage') }}
				</button>
				<button type="button" id="gdpr-delete"
					class="custom-bg-color-primary hover:opacity-50 {{ $text_color }} px-4 py-2 text-sm">
					{{ __('Delete My Data', 'sage') }}
				</button>
				<button type="button" id="et-manage-cookies" class="et-btn et-btn-link hover:opacity-50"
					onclick="manageCookies(); return false;">
					{{ __('Manage Cookies', 'sage') }}
				</button>
			</div>

			<div id="gdpr-status" class="mt-3 p-3 text-sm" style="display: none;"></div>
		</form>
	</div>
@endsection