<?php

namespace Database\Seeders;

use App\Models\AnnouncementBanner;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the store's editorial content: FAQs, About Us / Privacy Policy,
 * contact info, a demo coupon and an announcement banner.
 *
 * Idempotent and non-destructive: FAQs upsert by question, settings are
 * only written when currently empty (so live edits from the admin panel
 * are never overwritten), and the coupon/banner use firstOrCreate.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqs();
        $this->seedSettings();
        $this->seedCoupon();
        $this->seedBanner();

        $this->command->info('✓ Demo content seeded (FAQs, settings, coupon, banner).');
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'q' => 'How does delivery and payment work?',
                'a' => "We deliver across Lebanon and payment is cash on delivery — you pay the courier when the order is in your hands. The delivery charge (and the free-shipping threshold, when active) is shown clearly at checkout before you confirm, so there are no surprises.",
            ],
            [
                'q' => 'Can I cancel my order?',
                'a' => "Yes. Pending orders can be cancelled within 24 hours of placing them, straight from the My Orders page — no phone calls needed. The items go back into stock immediately. Once an order has been approved for delivery, it can no longer be cancelled online; contact us and we will sort it out.",
            ],
            [
                'q' => 'How do I know my size is actually in stock?',
                'a' => "Stock on every product page is live and tracked per size and per color. If you can select it, we have it. If a size is out of stock, it appears disabled — we would rather show you that upfront than take an order we cannot fulfill.",
            ],
            [
                'q' => 'What is the virtual try-on and is my photo safe?',
                'a' => "On any product page you can upload a photo of yourself and get an AI-generated preview of how the garment looks on you. Your photo is processed in memory and never saved on our servers — it is discarded the moment your preview is generated. There is a limit of 5 try-ons per minute per account.",
            ],
            [
                'q' => 'What can the shopping assistant help me with?',
                'a' => "Ask it anything you would ask a shop assistant in person: \"something for running under \$50\", \"do you have compression shirts in medium?\", \"what goes with these leggings?\". It knows the live catalog and only suggests items that are actually in stock. You will find it in the corner of every page once you are signed in.",
            ],
            [
                'q' => 'How do coupon codes work?',
                'a' => "Enter your code on the checkout page and the discount is applied instantly, before you confirm anything. Codes can be percentage-based or a fixed amount, and some have a minimum order value or an expiry date — if a code is refused, the message will tell you exactly why.",
            ],
            [
                'q' => 'Do I need an account to order?',
                'a' => "Yes — your account is what powers order tracking, cancellations, your cart syncing across devices, and your wishlist. Registration takes under a minute, or you can sign in with your Google account and skip the password entirely.",
            ],
            [
                'q' => 'Can I leave a review?',
                'a' => "Absolutely, and please do — reviews are how other customers decide. Each customer can leave one review per product (a rating out of 5 plus an optional comment), and you can delete your own review at any time.",
            ],
            [
                'q' => 'What if there is a problem with my order?',
                'a' => "Wrong size delivered, defect, missing item — email us at xtremefit750@gmail.com or use the Contact Us page with your order number, and we will make it right. We are a small team; a real person reads every message, usually within the day.",
            ],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['q']],
                ['answer' => $faq['a'], 'sort_order' => $i + 1]
            );
        }
    }

    private function seedSettings(): void
    {
        $defaults = [
            'contact_email'   => 'xtremefit750@gmail.com',
            'contact_phone'   => '+961 70 000 000',
            'contact_address' => 'Beirut, Lebanon',
            'about_us'        => "XTREMEFIT started with a simple frustration: finding good training gear in Lebanon meant either overpaying for imported brands or gambling on quality from an Instagram page with no sizes listed and no way to return anything.\n\nWe are a small team of athletes and lifters who decided to do it properly. Every piece in our catalog is something we would train in ourselves — compression wear that actually holds its shape after fifty washes, footwear with real support, and sets designed for the gym floor but comfortable enough for everything around it.\n\nWhat we care about:\n\nHonest sizing. Every product page shows exactly which sizes are in stock right now — no ordering a medium and being told a week later that only XXL is left. Our stock counts are live, per size and per color.\n\nSee it before you buy it. Our virtual try-on lets you upload a photo and see how a piece actually looks on you, not on a model. And if you are not sure what you need, ask our shopping assistant — it knows the full catalog and answers in plain language.\n\nNo payment games. We deliver across Lebanon with cash on delivery. You pay when the order is in your hands. Pending orders can be cancelled within 24 hours, no questions asked, and stock goes straight back on the shelf.\n\nWe are still growing, and we read every review and every message. If something is not right with your order, tell us — fixing it fast is the whole point of doing this ourselves.",
            'privacy_policy'  => "Last updated: July 2026\n\nThis policy explains what information XTREMEFIT collects when you use our website, why we collect it, and what we do (and deliberately do not do) with it.\n\n1. What we collect\n\nAccount information: your name, email address, and optionally a phone number and delivery address when you register or update your profile. Passwords are stored hashed — we cannot read them.\n\nOrders: the items you purchase, the delivery address you provide, and, if you choose to pin your location on the map at checkout, the coordinates you select. We keep order history so you can track past purchases and so we can handle cancellations and disputes.\n\nReviews and messages: product reviews you post and questions you send to the shopping assistant.\n\nSign-in with Google: if you use Google to sign in, we receive your name and email address from Google. We do not receive or store your Google password.\n\n2. Virtual try-on photos\n\nWhen you use the virtual try-on feature, the photo you upload is processed in memory and sent to our image-generation partner to create the preview. We never save your photo on our servers — it is discarded as soon as your request completes. Only the generated preview is returned to you.\n\n3. Payments\n\nWe operate on cash on delivery. We do not collect, process, or store any card numbers or banking details, because we never ask for them.\n\n4. How we use your information\n\nTo process and deliver your orders, to email you when your order status changes, to answer your questions, and to keep the store secure (for example, limiting repeated failed login attempts). We do not sell your data, and we do not send marketing emails you did not ask for.\n\n5. Third-party services\n\nWe use a small number of external services to make features work: Google (optional sign-in), an AI provider to power the shopping assistant, and an image-generation service for the virtual try-on. Each receives only the minimum needed to perform its function.\n\n6. Cookies and sessions\n\nWe use authentication tokens to keep you signed in. We do not use advertising or tracking cookies.\n\n7. Your choices\n\nYou can update your profile information at any time from your account page, delete your own product reviews, and cancel pending orders within 24 hours. If you want your account removed entirely, contact us and we will handle it.\n\n8. Contact\n\nQuestions about this policy or your data: xtremefit750@gmail.com.",
            'terms_of_service' => "Last updated: August 2026\n\nThese terms cover buying from XTREMEFIT. By placing an order or using an account on this site, you agree to them.\n\n1. Who can order\n\nYou need to be at least 18, or have permission from a parent or guardian, and you need to give us a real name, phone number and delivery address. We deliver within Lebanon only.\n\n2. Your account\n\nKeep your password to yourself — anything ordered from your account is treated as ordered by you. Tell us straight away if you think someone else has access to it. We may suspend an account that is being used fraudulently or abusively.\n\n3. Products, sizing and stock\n\nWe describe our products as accurately as we can, but screen colours vary and fit differs between brands. Stock counts on the site are live, though in rare cases an item sells out between you adding it to your cart and us confirming the order. If that happens we will contact you and cancel or substitute with your agreement.\n\n4. Prices\n\nPrices are shown in US dollars and include any applicable taxes. We can change prices at any time, but never after you have placed an order. If an item is listed at an obviously wrong price because of a technical or human error, we may cancel the order and refund you rather than honour it — we will always tell you first.\n\n5. Placing an order\n\nSubmitting an order is an offer to buy, not a completed sale. The contract exists once we confirm your order by email. We may decline an order if the item is unavailable, the delivery address is outside our range, or we have reason to think the order is fraudulent.\n\n6. Payment\n\nWe take cash on delivery. You pay the courier when your order arrives. We do not ask for, collect, or store card or bank details anywhere on this site.\n\n7. Delivery\n\nWe deliver across Lebanon. Delivery timeframes shown on the site are estimates, not guarantees — road conditions and courier availability affect them. Delivery charges, and the order value above which delivery is free, are shown at checkout before you confirm.\n\nIf you pin your location on the map at checkout, we use it to help the courier find you. Please make sure someone can receive the order at the address you gave us.\n\n8. Cancellations\n\nYou can cancel an order yourself from your account while it is still pending, within 24 hours of placing it. After it has been approved and handed to the courier, contact us and we will cancel it if it has not yet shipped.\n\n9. Returns and exchanges\n\nYou can return an unworn item in its original condition, with tags attached, within 14 days of delivery. Underwear and swimwear cannot be returned once the hygiene seal is broken. Sale items can be exchanged for a different size but not refunded.\n\nIf an item arrives faulty, damaged or not what you ordered, tell us within 48 hours and we will collect it and replace or refund it at our cost.\n\n10. Coupons\n\nDiscount codes have expiry dates, may require a minimum order value, and may be limited to certain products. Unless we say otherwise, one code per order. We may withdraw a code that is being abused or that was published in error.\n\n11. Reviews and content you post\n\nYou can review products you have bought. Keep reviews about the product — we remove reviews that are abusive, contain personal information, are advertising, or are clearly not from a real purchase. By posting a review you let us display it on the site. You can delete your own reviews at any time.\n\n12. Virtual try-on and the shopping assistant\n\nThe virtual try-on produces an approximate preview generated by software. It is a visual aid, not a guarantee of fit, colour or appearance, and it is not a reason to skip our size guide. Only upload photos of yourself, or of someone who has agreed to it. Your photo is processed and then discarded — see our Privacy Policy.\n\nThe shopping assistant makes suggestions based on our catalogue. It can be wrong about availability, price or suitability. Always check the product page before you order — the product page is what counts.\n\n13. Our content\n\nThe XTREMEFIT name, logo, site design, text and photography belong to us. Product names and brand logos belong to their respective owners. Do not copy our content for commercial use without asking us.\n\n14. Using the site properly\n\nDo not attempt to break into the site, scrape it in bulk, overload it, or use it to place fake orders. We log and block this kind of activity.\n\n15. Liability\n\nWe are responsible for delivering the products you ordered, in the condition described. We are not liable for injury arising from how you use sportswear or equipment, or for losses we could not reasonably have foreseen. Nothing here limits liability that cannot be limited under Lebanese law.\n\n16. Changes\n\nWe may update these terms. The version published here when you place an order is the version that applies to that order.\n\n17. Governing law\n\nThese terms are governed by the laws of Lebanon, and the courts of Beirut have jurisdiction over any dispute.\n\n18. Contact\n\nQuestions about an order or these terms: xtremefit750@gmail.com.",
        ];

        foreach ($defaults as $key => $value) {
            // Never overwrite content the admin already entered
            if (StoreSetting::getValue($key, '') === '') {
                StoreSetting::setValue($key, $value);
            }
        }
    }

    private function seedCoupon(): void
    {
        Coupon::firstOrCreate(
            ['code' => 'CAPSTONE20'],
            [
                'discount_type'  => 'percentage',
                'discount_value' => 20,
                'is_active'      => true,
            ]
        );
    }

    private function seedBanner(): void
    {
        AnnouncementBanner::firstOrCreate(
            ['message' => 'New season live — free delivery on orders over $100'],
            ['is_active' => true, 'sort_order' => 1]
        );
    }
}
