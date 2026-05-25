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
                'slug'            => 'about-us',
                'title'           => 'About Us',
                'menu_section_id' => 2,
                'description'     => fn () => $this->aboutUsContent(),
            ],
            [
                'slug'             => 'contact-us',
                'title'            => 'Contact Us',
                'menu_section_id'  => 1,
                'menu_template_id' => 1,
                'description'      => fn () => $this->contactUsContent(),
            ],
            [
                'slug'            => 'faq',
                'title'           => 'FAQ',
                'menu_section_id' => 1,
                'description'     => fn () => $this->faqContent(),
            ],
            [
                'slug'            => 'shipping-policy',
                'title'           => 'Shipping Policy',
                'menu_section_id' => 1,
                'description'     => fn () => $this->shippingPolicyContent(),
            ],
            [
                'slug'            => 'return-and-refund-policy',
                'title'           => 'Return & Refund Policy',
                'menu_section_id' => 1,
                'description'     => fn () => $this->returnRefundContent(),
            ],
            [
                'slug'            => 'privacy-policy',
                'title'           => 'Privacy Policy',
                'menu_section_id' => 2,
                'description'     => fn () => $this->privacyPolicyContent(),
            ],
            [
                'slug'            => 'terms-and-conditions',
                'title'           => 'Terms & Conditions',
                'menu_section_id' => 2,
                'description'     => fn () => $this->termsContent(),
            ],
        ];
    }

    private function company(): array
    {
        $company = Settings::group('company')->all();

        $name = trim((string) ($company['company_name'] ?? config('app.name', 'Our Store')));
        $email = trim((string) ($company['company_email'] ?? ''));
        $phone = trim((string) (($company['company_calling_code'] ?? '') . ($company['company_phone'] ?? '')));
        $address = trim((string) ($company['company_address'] ?? ''));
        $city = trim((string) ($company['company_city'] ?? ''));
        $state = trim((string) ($company['company_state'] ?? ''));
        $zip = trim((string) ($company['company_zip_code'] ?? ''));
        $website = trim((string) ($company['company_website'] ?? config('app.url', '')));

        $locationParts = array_filter([$address, $city, $state, $zip]);

        return [
            'name'      => $name,
            'email'     => $email,
            'phone'     => $phone,
            'address'   => implode(', ', $locationParts) ?: $address,
            'website'   => $website,
            'cod_enabled' => (int) (Settings::group('site')->get('site_cash_on_delivery') ?? 10) === Status::ACTIVE,
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
        return '<section class="store-page-section"><h2>' . $this->e($title) . '</h2>' . $body . '</section>';
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

        $sections = $this->section('Who We Are', '<p>Welcome to <strong>' . $name . '</strong>. We are an online store committed to quality products, secure checkout, and reliable support from browse to delivery.</p>')
            . $this->section('Our Mission', '<p>We make shopping simple and transparent—on mobile or desktop—with clear policies and responsive customer care.</p>')
            . $this->section('What You Can Expect', $this->list([
                'Accurate product descriptions and pricing',
                'Secure payment options and order tracking',
                'Clear shipping, returns, and privacy policies',
            ]));

        if ($c['address'] !== '' || $c['email'] !== '' || $c['phone'] !== '') {
            $contact = '';
            if ($c['address'] !== '') {
                $contact .= '<p><strong>Address:</strong> ' . $this->e($c['address']) . '</p>';
            }
            if ($c['email'] !== '') {
                $contact .= '<p><strong>Email:</strong> <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a></p>';
            }
            if ($c['phone'] !== '') {
                $contact .= '<p><strong>Phone:</strong> ' . $this->e($c['phone']) . '</p>';
            }
            $sections .= $this->section('Contact', $contact);
        }

        return $this->wrap('Learn more about ' . $name . '.', $sections);
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

        $sections = $this->section('Get In Touch', '<p>The ' . $name . ' team is here to help with orders, products, and general questions. Include your order number when writing about an existing purchase.</p>')
            . $this->section('Contact Channels', $channels !== [] ? $this->list($channels) : '<p>Use the contact details in the sidebar.</p>')
            . $this->section('Response Time', '<p>We aim to reply within 1–2 business days. Messages on weekends are handled on the next business day.</p>');

        return $this->wrap('We would love to hear from you.', $sections);
    }

    private function faqContent(): string
    {
        $store = $this->e($this->company()['name']);

        $sections = $this->section('Orders & Tracking', '<p><strong>How do I track my order?</strong><br>Use <em>Track Order</em> in the footer with your order number and checkout email.</p><p><strong>Can I cancel my order?</strong><br>Contact us immediately after ordering; we will try to help before dispatch.</p>')
            . $this->section('Shipping & Delivery', '<p><strong>How long does delivery take?</strong><br>See our <a href="/page/shipping-policy">Shipping Policy</a> for timelines shown at checkout.</p>')
            . $this->section('Returns & Refunds', '<p><strong>How do I return an item?</strong><br>See our <a href="/page/return-and-refund-policy">Return & Refund Policy</a> for steps and eligibility.</p>')
            . $this->section('Payments', '<p><strong>Is checkout secure?</strong><br>' . $store . ' uses industry-standard security. See our <a href="/page/privacy-policy">Privacy Policy</a>.</p>');

        return $this->wrap('Quick answers about shopping with ' . $store . '.', $sections);
    }

    private function shippingPolicyContent(): string
    {
        $c = $this->company();
        $cod = $c['cod_enabled'] ? '<p>Cash on delivery may be available at checkout where offered.</p>' : '';

        $sections = $this->section('Processing', '<p>Orders are usually processed within 1–3 business days after payment confirmation.</p>')
            . $this->section('Delivery', '<p>Shipping fees and delivery estimates are shown at checkout based on your address.</p>')
            . $this->section('Restrictions', '<p>Some areas or products may not be eligible for all carriers; we will contact you if we cannot ship your order.</p>')
            . ($cod !== '' ? $this->section('Cash on Delivery', $cod) : '');

        return $this->wrap('How ' . $this->e($c['name']) . ' ships your order.', $sections);
    }

    private function returnRefundContent(): string
    {
        $c = $this->company();
        $contact = $c['email'] !== ''
            ? '<p>Contact <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a> with your order number to start a return.</p>'
            : '<p>Contact customer support with your order number to start a return.</p>';

        $sections = $this->section('Eligibility', '<p>Items should be unused and in original packaging with proof of purchase. Some categories may be non-returnable unless defective.</p>')
            . $this->section('Return Window', '<p>Eligible items may be returned within 14 days of delivery unless stated otherwise on the product page.</p>')
            . $this->section('How to Return', $contact)
            . $this->section('Refunds', '<p>Approved refunds are issued to the original payment method after inspection.</p>');

        return $this->wrap('Fair returns and refunds at ' . $this->e($c['name']) . '.', $sections);
    }

    private function privacyPolicyContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $sections = $this->section('Information We Collect', '<p>We may collect contact details, order information, device data, and cookies needed to run and improve the store.</p>')
            . $this->section('How We Use Data', $this->list([
                'Process orders and provide support',
                'Improve our website and services',
                'Prevent fraud and maintain security',
            ]))
            . $this->section('Your Rights', '<p>You may request access or correction of your data by contacting us.</p>')
            . $this->section('Contact', '<p>Privacy questions: ' . $name . ($c['email'] !== '' ? ' — <a href="mailto:' . $this->e($c['email']) . '">' . $this->e($c['email']) . '</a>' : '') . '.</p>');

        return $this->wrap('How ' . $name . ' handles your personal information.', $sections);
    }

    private function termsContent(): string
    {
        $c = $this->company();
        $name = $this->e($c['name']);

        $sections = $this->section('Agreement', '<p>By using ' . $name . ' you agree to these terms and our Privacy Policy.</p>')
            . $this->section('Orders', '<p>Prices and availability may change. We may cancel orders for stock, pricing, or verification issues.</p>')
            . $this->section('Policies', '<p>See our <a href="/page/shipping-policy">Shipping Policy</a> and <a href="/page/return-and-refund-policy">Return & Refund Policy</a>.</p>')
            . $this->section('Changes', '<p>We may update these terms; continued use means acceptance of updates.</p>');

        return $this->wrap('Please read these terms before using ' . $name . '.', $sections);
    }
}
