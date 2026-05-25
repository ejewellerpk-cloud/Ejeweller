<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Page;
use Dipokhalder\Settings\Facades\Settings;
use Illuminate\Support\Facades\Log;

class DefaultPagesService
{
    public function ensure(): void
    {
        foreach ($this->definitions() as $definition) {
            try {
                Page::firstOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'title'            => $definition['title'],
                        'description'      => $definition['description'](),
                        'menu_section_id'  => $definition['menu_section_id'],
                        'menu_template_id' => $definition['menu_template_id'] ?? null,
                        'status'           => Status::ACTIVE,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Default page seed skipped: ' . $definition['slug'], ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * @return array<int, array{slug: string, title: string, menu_section_id: int, menu_template_id?: int|null, description: callable}>
     */
    private function definitions(): array
    {
        return [
            [
                'slug'             => 'about-us',
                'title'            => 'About Us',
                'menu_section_id'  => 2,
                'description'      => fn () => $this->aboutUsContent(),
            ],
            [
                'slug'             => 'contact-us',
                'title'            => 'Contact Us',
                'menu_section_id'  => 1,
                'menu_template_id' => 1,
                'description'      => fn () => $this->contactUsContent(),
            ],
            [
                'slug'             => 'faq',
                'title'            => 'FAQ',
                'menu_section_id'  => 1,
                'description'      => fn () => $this->faqContent(),
            ],
            [
                'slug'             => 'shipping-policy',
                'title'            => 'Shipping Policy',
                'menu_section_id'  => 1,
                'description'      => fn () => $this->shippingPolicyContent(),
            ],
            [
                'slug'             => 'return-and-refund-policy',
                'title'            => 'Return & Refund Policy',
                'menu_section_id'  => 1,
                'description'      => fn () => $this->returnRefundContent(),
            ],
            [
                'slug'             => 'privacy-policy',
                'title'            => 'Privacy Policy',
                'menu_section_id'  => 2,
                'description'      => fn () => $this->privacyPolicyContent(),
            ],
            [
                'slug'             => 'terms-and-conditions',
                'title'            => 'Terms & Conditions',
                'menu_section_id'  => 2,
                'description'      => fn () => $this->termsContent(),
            ],
        ];
    }

    private function company(): array
    {
        $company = Settings::group('company')->all();
        $site    = Settings::group('site')->all();

        $name = trim((string) ($company['company_name'] ?? config('app.name', 'Our Store')));
        $email = trim((string) ($company['company_email'] ?? ''));
        $phone = trim((string) (($company['company_calling_code'] ?? '') . ($company['company_phone'] ?? '')));
        $address = trim((string) ($company['company_address'] ?? ''));
        $city = trim((string) ($company['company_city'] ?? ''));
        $state = trim((string) ($company['company_state'] ?? ''));
        $zip = trim((string) ($company['company_zip_code'] ?? ''));
        $website = trim((string) ($company['company_website'] ?? config('app.url', '')));

        $locationParts = array_filter([$address, $city, $state, $zip]);
        $fullAddress = implode(', ', $locationParts);

        $flatShipping = $site['shipping_setup_flat_rate_wise_cost'] ?? null;
        $codEnabled = (int) ($site['site_cash_on_delivery'] ?? 10) === Status::ACTIVE;

        return [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'address'       => $fullAddress ?: $address,
            'website'       => $website,
            'flat_shipping' => $flatShipping,
            'cod_enabled'   => $codEnabled,
        ];
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function wrap(string $intro, string $sectionsHtml): string
    {
        $introBlock = $intro !== ''
            ? '<p class="store-page-lead">' . $intro . '</p>'
            : '';

        return '<div class="store-page-body">' . $introBlock . $sectionsHtml . '</div>';
    }

    private function section(string $title, string $body): string
    {
        return '<section class="store-page-section">'
            . '<h2>' . $this->e($title) . '</h2>'
            . $body
            . '</section>';
    }

    private function list(array $items): string
    {
        $lis = '';
        foreach ($items as $item) {
            $lis .= '<li>' . $item . '</li>';
        }

        return '<ul class="store-page-list">' . $lis . '</ul>';
    }

    private function aboutUsContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $sections = $this->section('Who We Are', '<p>Welcome to <strong>' . $name . '</strong>. We are an online store committed to offering quality products, secure checkout, and reliable customer support from browse to delivery.</p>')
            . $this->section('Our Mission', '<p>Our mission is to make shopping simple, transparent, and enjoyable—whether you shop on mobile or desktop. We continuously improve our catalog, fulfillment, and service based on customer feedback.</p>')
            . $this->section('What You Can Expect', $this->list([
                'Clear product information and pricing',
                'Secure payment options and order tracking',
                'Responsive support for orders, returns, and general questions',
                'Policies that explain shipping, returns, and privacy in plain language',
            ]));

        if ($c['address'] !== '') {
            $sections .= $this->section('Visit & Contact', '<p>Registered address: ' . $this->e($c['address']) . '</p>'
                . ($c['email'] !== '' ? '<p>Email: <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a></p>' : '')
                . ($c['phone'] !== '' ? '<p>Phone: ' . $this->e($c['phone']) . '</p>' : ''));
        }

        return $this->wrap(
            'Learn more about ' . $name . ' and how we serve our customers.',
            $sections
        );
    }

    private function contactUsContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $channels = [];
        if ($c['email'] !== '') {
            $channels[] = '<strong>Email:</strong> <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a>';
        }
        if ($c['phone'] !== '') {
            $channels[] = '<strong>Phone:</strong> ' . $this->e($c['phone']);
        }
        if ($c['address'] !== '') {
            $channels[] = '<strong>Address:</strong> ' . $this->e($c['address']);
        }
        if ($c['website'] !== '') {
            $channels[] = '<strong>Website:</strong> <a href="' . $this->e($c['website']) . '" target="_blank" rel="noopener noreferrer">' . $this->e($c['website']) . '</a>';
        }

        $channelHtml = $channels !== []
            ? $this->list($channels)
            : '<p>Please use the contact details shown in the sidebar or footer of our store.</p>';

        $sections = $this->section('Get In Touch', '<p>Have a question about an order, product, or partnership? The ' . $name . ' team is here to help. Reach out using any of the channels below and include your order number when applicable so we can assist you faster.</p>')
            . $this->section('Customer Support', $channelHtml)
            . $this->section('Business Hours', '<p>We aim to respond to all inquiries within 1–2 business days. Messages received on weekends or public holidays are handled on the next business day.</p>');

        return $this->wrap(
            'Questions, order updates, or feedback—we would love to hear from you.',
            $sections
        );
    }

    private function faqContent(): string
    {
        $c = $this->company();
        $store = $this->e($c['name']);

        $sections = $this->section('Orders & Tracking', '<p><strong>How do I track my order?</strong><br>Use the <em>Track Order</em> link in the footer. Enter your order number and the email used at checkout to view status and updates.</p>'
            . '<p><strong>Can I change or cancel my order?</strong><br>Contact us as soon as possible after placing your order. If fulfillment has not started, we will try to accommodate changes or cancellation.</p>')
            . $this->section('Shipping & Delivery', '<p><strong>How long does delivery take?</strong><br>Delivery times depend on your location and the shipping method selected at checkout. See our <a href="/page/shipping-policy">Shipping Policy</a> for details.</p>'
            . '<p><strong>Do you ship internationally?</strong><br>Available regions are shown at checkout. If your area is not listed, contact support before ordering.</p>')
            . $this->section('Returns & Refunds', '<p><strong>How do I return an item?</strong><br>Review our <a href="/page/return-and-refund-policy">Return & Refund Policy</a> for eligibility, timelines, and steps to start a return.</p>'
            . '<p><strong>When will I receive my refund?</strong><br>Approved refunds are processed to your original payment method. Bank processing times may add 3–10 business days.</p>')
            . $this->section('Payments & Account', '<p><strong>Which payment methods do you accept?</strong><br>We accept the payment methods displayed at checkout, which may include cards, wallets, and cash on delivery where enabled.</p>'
            . '<p><strong>Is my payment information secure?</strong><br>' . $store . ' uses industry-standard security practices. See our <a href="/page/privacy-policy">Privacy Policy</a> for more information.</p>');

        return $this->wrap(
            'Quick answers to common questions about shopping with ' . $store . '.',
            $sections
        );
    }

    private function shippingPolicyContent(): string
    {
        $c = $this->company();
        $shippingNote = $c['flat_shipping'] !== null && $c['flat_shipping'] !== ''
            ? '<p>Standard flat-rate shipping may apply as configured for our store (rate shown at checkout).</p>'
            : '<p>Shipping fees and estimated delivery windows are calculated at checkout based on your address and order total.</p>';

        $codNote = $c['cod_enabled']
            ? '<p>Cash on delivery may be available for eligible orders—select it at checkout if offered.</p>'
            : '';

        $sections = $this->section('Processing Time', '<p>Orders are typically processed within 1–3 business days after payment confirmation. Processing may take longer during peak seasons or for customized items.</p>')
            . $this->section('Delivery Methods', $shippingNote . '<p>Once shipped, you will receive tracking information by email or SMS when available.</p>')
            . $this->section('Shipping Restrictions', '<p>Some remote areas, P.O. boxes, or restricted products may not be eligible for certain carriers. If we cannot ship to your address, we will contact you to arrange an alternative or refund.</p>')
            . $this->section('Lost or Damaged Packages', '<p>If your package arrives damaged or goes missing in transit, contact us within 48 hours of the marked delivery date with photos and your order number. We will work with the carrier to resolve the issue.</p>')
            . ($codNote !== '' ? $this->section('Cash on Delivery', $codNote) : '');

        return $this->wrap(
            'Everything you need to know about how ' . $this->e($c['name']) . ' ships your order.',
            $sections
        );
    }

    private function returnRefundContent(): string
    {
        $c = $this->company();
        $contact = $c['email'] !== ''
            ? '<p>To start a return, contact <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a> with your order number and reason for return.</p>'
            : '<p>To start a return, contact customer support with your order number and reason for return.</p>';

        $sections = $this->section('Return Eligibility', '<p>Items must be unused, in original packaging, and accompanied by proof of purchase. Certain categories (e.g. intimate apparel, personalized goods, or perishables) may be non-returnable unless defective.</p>')
            . $this->section('Return Window', '<p>Most eligible items may be returned within 14 days of delivery unless otherwise stated on the product page. Defective or incorrect items should be reported within 48 hours of receipt.</p>')
            . $this->section('How to Return', $contact)
            . $this->section('Refunds', '<p>After we receive and inspect your return, approved refunds are issued to the original payment method. Shipping fees and promotional discounts may be non-refundable unless required by law or the return is due to our error.</p>')
            . $this->section('Exchanges', '<p>We do not guarantee direct exchanges. For a different size or variant, return the original item (when eligible) and place a new order.</p>');

        return $this->wrap(
            'Our commitment to fair, clear returns and refunds at ' . $this->e($c['name']) . '.',
            $sections
        );
    }

    private function privacyPolicyContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $sections = $this->section('Information We Collect', '<p>When you use our store, we may collect information you provide (name, email, phone, shipping address), order and payment details, device and browser data, and cookies used to operate and improve the site.</p>')
            . $this->section('How We Use Your Information', $this->list([
                'Process and fulfill orders',
                'Provide customer support and order updates',
                'Improve our website, products, and marketing (where permitted)',
                'Prevent fraud and maintain security',
            ]))
            . $this->section('Sharing of Data', '<p>We share data with service providers who help us operate the store (payment, shipping, analytics) under contractual obligations. We do not sell your personal information.</p>')
            . $this->section('Your Rights', '<p>Depending on your location, you may request access, correction, or deletion of your personal data. Contact us using the details on our Contact page.</p>')
            . $this->section('Data Security', '<p>We implement reasonable technical and organizational measures to protect your information. No method of transmission over the internet is 100% secure.</p>')
            . $this->section('Contact', '<p>For privacy-related questions, contact ' . $name
                . ($c['email'] !== '' ? ' at <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a>.' : '.'));

        return $this->wrap(
            'How ' . $name . ' collects, uses, and protects your personal information.',
            $sections
        );
    }

    private function termsContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $sections = $this->section('Agreement', '<p>By accessing or purchasing from ' . $name . ', you agree to these Terms & Conditions and our Privacy Policy. If you do not agree, please do not use the site.</p>')
            . $this->section('Products & Pricing', '<p>We strive to display accurate descriptions and prices. We reserve the right to correct errors, limit quantities, and refuse or cancel orders placed with incorrect pricing or availability information.</p>')
            . $this->section('Orders & Payment', '<p>Your order is confirmed when you receive an order confirmation. We may cancel orders for suspected fraud, stock issues, or failed payment verification.</p>')
            . $this->section('Shipping & Returns', '<p>Shipping and return rules are described in our <a href="/page/shipping-policy">Shipping Policy</a> and <a href="/page/return-and-refund-policy">Return & Refund Policy</a>.</p>')
            . $this->section('Intellectual Property', '<p>All content on this website—including text, images, logos, and design—is owned by or licensed to ' . $name . ' and may not be used without permission.</p>')
            . $this->section('Limitation of Liability', '<p>To the fullest extent permitted by law, ' . $name . ' is not liable for indirect or consequential damages arising from use of the site or products.</p>')
            . $this->section('Changes', '<p>We may update these terms from time to time. Continued use of the site after changes constitutes acceptance of the updated terms.</p>');

        return $this->wrap(
            'Please read these terms carefully before using ' . $name . '.',
            $sections
        );
    }
}
