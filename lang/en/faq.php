<?php

// FAQ content (v3.02): English edition, mirrors lang/de/faq.php.
return [
    'intro' => 'Quick guides to all core processes. Detailed descriptions are available in the knowledge base (user manual, process guide) and in the onboarding guide.',
    'disclaimer' => 'All information describes the system functions. Legal and regulatory questions (KWG, GwG, GDPR, consumer protection) must be reviewed by a lawyer/tax advisor before going live.',
    'items' => [
        [
            'q' => 'How do I create and maintain a new (medical) client?',
            'a' => "1. Sales & Clients → CRM / Sales: capture and qualify the lead.\n2. Sales & Clients → Onboarding: move the lead into the onboarding pipeline (data intake, documents).\n3. Complete KYC (see the dedicated FAQ) — no active contract without completed KYC.\n4. Sales & Clients → Clients → client detail page: verify master data, set the industry segment (physician, dentist, pharmacy, dental lab, veterinarian, …), B2B/B2C and the rating.\n5. Create the contract with individual terms (advance rate, fee, interest, term) and set up credit lines.\n6. Users → create the client login (client role, bound to the organization); the credentials are sent by e-mail with a password set-up link.",
        ],
        [
            'q' => 'How do I create an investor and record committed capital?',
            'a' => "1. Sales & Clients → Clients: create an organization of type investor (or have system administration create it).\n2. Treasury & Finance → Investors & Facilities: create the facility — committed capital, interest rate, term, ranking (senior/subordinated), special termination right yes/no and notice period.\n3. Drawdowns, interest payments and repayments are recorded as events on the facility.\n4. Users → create the investor login (investor role, bound to the investor organization). The investor then sees their investment, utilization and a clearly labelled model calculation under My Capital Relationship.\n5. Maintain the investor's rating on the organization page (relevant for internal concentration and reliability management).",
        ],
        [
            'q' => 'How does a factoring transaction run from submission to settlement?',
            'a' => "1. The client submits the receivable in the client portal (or operations records it).\n2. Operations: formal review → risk/limit check (automated rule check).\n3. Calculate the purchase: the system determines payout, security retention, fee (including the rating surcharge) and interest estimate.\n4. Four-eyes principle: a SECOND person grants the second approval — the same person cannot approve both.\n5. Treasury: build the payout batch → first approval → second approval (four eyes again) → SEPA file → bank confirmation.\n6. Incoming payments: bank transactions are matched to receivables (including cumulative partial payments).\n7. After full payment: settlement — the security retention is released. Every step is recorded in the tamper-evident audit log.",
        ],
        [
            'q' => 'How does the KYC process work?',
            'a' => "1. Client detail page → start the KYC review: identification of the organization and the acting persons.\n2. Record ultimate beneficial owners (UBO) and screen each of them against PEP/sanctions lists.\n3. Perform the register check (commercial/professional register).\n4. Credit check via the credit bureau interface (Creditreform/SCHUFA prepared, currently sandbox).\n5. Result and documents are recorded on the KYC case; the client only becomes active once KYC is completed. Schedule periodic re-reviews according to the risk class (AML cycle).",
        ],
        [
            'q' => 'How does the rating work and what does it do?',
            'a' => "Scale AAA (excellent) to C (at risk of default), derived from a score of 0–100. Maintained on the client detail page → Rating & Classification.\nEffect: each rating grade carries a fee surcharge on the contractual factoring fee (AAA: +0.0 percentage points up to C: +2.5 percentage points), applied automatically at purchase. The rating is an internal steering instrument, not an external credit report. It applies to clients AND investors.",
        ],
        [
            'q' => 'What is the difference between recourse and non-recourse factoring?',
            'a' => "Non-recourse (true) factoring: the factor assumes the default risk (del credere) — the client is not liable for the debtor's ability to pay.\nRecourse factoring: the default risk remains with the client (recourse possible).\nThe assignment is made on the factoring product (recourse type field) and shapes the contract, accounting and risk management. Also set on the product: disclosed factoring (the debtor is notified of the assignment) or undisclosed factoring (the debtor is not notified).",
        ],
        [
            'q' => 'What does B2B/B2C mean for clients and debtors?',
            'a' => "B2B: commercial counterparty — the assignment can be agreed without special form; a separate notice of assignment to the debtor is common in disclosed factoring and dispensable in undisclosed factoring.\nB2C: consumers as invoice recipients — additional requirements apply here (consumer protection, data protection, possibly information about the assignment). The classification is maintained on the organization page; have B2C constellations reviewed legally before going live.",
        ],
        [
            'q' => 'How do credit insurance and the concentration risk work?',
            'a' => "Credit lines above the threshold (default: EUR 30,000, configurable) are treated as concentration risk and should be fully or partially insured.\nFor each credit line you maintain the insurer, the insured amount and the status (not insured, applied for, insured, declined).\nThe interface to the insurer is prepared as an adapter: the target is a monthly line report (amount, internal rating, pseudonymized debtor) with feedback on acceptance and premium — the concrete design is negotiated with the insurer.",
        ],
        [
            'q' => 'What happens after a rejection? (front/back office escalation)',
            'a' => "Bank-style second-vote procedure following the front office/back office principle:\n1. If a receivable is rejected (e.g. creditworthiness, rule check), the front office (operations/sales) can request a second vote from the back office (credit/risk) with a written justification.\n2. The back office reviews manually and either approves OR rejects — in which case the case automatically escalates to the executive board.\n3. The executive board decides as the final instance (approve or reject definitively). Each stage requires a justification and is recorded in the tamper-evident audit log.\nThe supervisory board deliberately does not take part in operational decisions — it is a supervisory body and receives escalations in aggregated form in reporting. Even after an approval in the second vote, the four-eyes principle still applies to the purchase.",
        ],
        [
            'q' => 'When can an investor terminate early?',
            'a' => "Facilities can be terminated ordinarily (at maturity or with the agreed notice period), via a special termination right (only if contractually agreed and recorded in the system) or due to the investor's insolvency.\nThe termination is recorded under Investors & Facilities with its reason; notice period and event are logged. Repayment of drawn capital is a treasury process and traceable via the event log.",
        ],
        [
            'q' => 'How do I create a support ticket and who answers it?',
            'a' => "Menu Support → create a new ticket (subject, category, description). Clients and investors only see their own tickets.\nInternal agents reply, can add internal notes (invisible to the requester) and maintain the status: open → in progress → answered → closed.",
        ],
        [
            'q' => 'How do I create users and maintain the personnel file?',
            'a' => "Administration (top right) → Users (system administration/executive board/superadmin only): choose name, e-mail and role; for client roles also the client organization.\nThe credentials are automatically sent to the new user by e-mail with a password set-up link. Internal roles, investors and advisory board members must set up two-factor authentication at first login (scan the QR code or enter the key manually).\nVia Edit you maintain the personnel file: position, department, functional and disciplinary supervisor, contact data, address, records (ID card, criminal record certificate, SCHUFA, driver's license) as well as joining and leaving dates. Accounts are only usable from the joining date and are automatically locked after the leaving date.",
        ],
        [
            'q' => 'What do the advisory/supervisory board see and not see?',
            'a' => "The advisory/supervisory board get their own dashboard with economic KPIs and charts (portfolio, ratios, concentration) as well as released board documents in the data room.\nThey have no access to operational data: client identities, debtor lists, individual receivables and internal processes are blocked server-side (medical data firewall) — even on direct URL access.",
        ],
        [
            'q' => 'Where do I find the chronology of all changes?',
            'a' => 'Click the version number in the footer or go to Administration (top right) → Changelog: all releases with date, time, responsible person and list of changes.',
        ],
    ],
];
