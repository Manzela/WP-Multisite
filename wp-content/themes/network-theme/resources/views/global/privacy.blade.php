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
			<p>[content in jurisdictional language]01/07/2025</p>

			<p>[content in jurisdictional language]'[content in jurisdictional language]"[content in jurisdictional language]"[content in jurisdictional language]" [content in jurisdictional language]"[content in jurisdictional language]") [content in jurisdictional language]				[content in jurisdictional language]"[content in jurisdictional language]"), [content in jurisdictional language]				[content in jurisdictional language]"[content in jurisdictional language]1981, [content in jurisdictional language]GDPR [content in jurisdictional language]				[content in jurisdictional language]</p>

			<ol class="list-decimal mr-8 space-y-3">
				<li>[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]"[content in jurisdictional language]"[content in jurisdictional language]").</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					<br>4.1 [content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]"[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]/ [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
					<br>4.2 [content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]IP [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					<br><br>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]Cookies) ([content in jurisdictional language]Google Analytics, Hotjar), [content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]Google Cloud Storage [content in jurisdictional language]</li>

				<li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]					[content in jurisdictional language]Google Cloud Storage) [content in jurisdictional language]</li>

				<li>[content in jurisdictional language]AI [content in jurisdictional language]					[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]IP, [content in jurisdictional language]</li>
					</ul>
					[content in jurisdictional language]					[content in jurisdictional language]				</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]/ [content in jurisdictional language]/ [content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
					[content in jurisdictional language]				</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]/[content in jurisdictional language]/[content in jurisdictional language]							[content in jurisdictional language]							[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]							[content in jurisdictional language]							[content in jurisdictional language]</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]Google Cloud, AWS).
					[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]Standard Contractual Clauses).</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]"[content in jurisdictional language]");</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
					[content in jurisdictional language]info@example-network.com.
				</li>

				<li>[content in jurisdictional language]16 [content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]					[content in jurisdictional language]</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]24 [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]12 [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]6 [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]</li>
					</ul>
				</li>

				<li>[content in jurisdictional language]					<ul class="list-disc mr-8 mt-2 space-y-1">
						<li>[content in jurisdictional language]'[content in jurisdictional language]"[content in jurisdictional language]</li>
						<li>[content in jurisdictional language]9, [content in jurisdictional language]</li>
						<li>[content in jurisdictional language]"[content in jurisdictional language]info@example-network.com</li>
					</ul>
				</li>
			</ol>
		</div>

	@else
		{{-- English content --}}
		<div class="space-y-4 mt-4">
			<p>Last updated: July 1, 2025</p>

			<p>[Company Name] Ltd. (the "Company" or "we") respects the privacy of users of its website and any
				other site operated by or service provided by the Company through the internet (the "Website"), and is committed
				to complying with applicable privacy protection standards under applicable local law, including the Protection of Privacy
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
					Information may be transferred outside the data subject jurisdiction (e.g., Google Cloud, AWS). In such cases, we rely
					on legal safeguards such as:
					<ul class="list-disc ml-8 mt-2 space-y-1">
						<li>applicable cross-border data transfer mechanisms (GDPR, CCPA, or similar);</li>
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
					<li>[Company Name] Ltd.</li>
					<li>[REGISTERED COMPANY ADDRESS]</li>
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