<?php

class KnowledgeBase
{
    private array $entries = [];

    public function __construct()
    {
        $this->loadDefaultKnowledge();
    }

    public function getAll(): array
    {
        return $this->entries;
    }

    public function getContextString(): string
    {
        $parts = [];
        foreach ($this->entries as $entry) {
            $parts[] = $entry['content'];
        }
        return implode("\n\n", $parts);
    }

    public function search(string $query): array
    {
        $results = [];
        $query = mb_strtolower($query);
        foreach ($this->entries as $entry) {
            $content = mb_strtolower($entry['content'] . ' ' . ($entry['keywords'] ?? ''));
            if (str_contains($content, $query)) {
                $results[] = $entry;
            }
        }
        return $results;
    }

    private function addEntry(string $category, string $content, string $keywords = ''): void
    {
        $this->entries[] = [
            'category' => $category,
            'content' => $content,
            'keywords' => $keywords,
        ];
    }

    private function loadDefaultKnowledge(): void
    {
        $this->addEntry('about_founder', '
Mr. Nas (Nasir Uddin) is the Founder of Nas Digital and Nasir Digital Consultancy Ltd UK.
He is a digital growth strategist, entrepreneur, and mentor who helps individuals and businesses build sustainable digital income streams and grow their brands.
He was born in Bangladesh and now operates internationally from the United Kingdom.
Mr. Nas is passionate about entrepreneurship, personal branding, digital marketing, and AI automation.
His mission is to empower people with the tools, systems, and mindset needed to succeed in the digital economy.
He believes in disciplined execution, creative strategy, and continuous learning.
He shares his knowledge through training programs, YouTube videos, and his Digital Growth Blueprint.
Mr. Nas is approachable, business-minded, and committed to helping others achieve financial freedom through digital business.
', 'nasir uddin, founder, mr nas, entrepreneur, digital strategist, mentor, ceo, owner, bio, who is');

        $this->addEntry('about_nasdigital', '
Nas Digital is a UK-based digital growth agency founded by Mr. Nas (Nasir Uddin).
Nas Digital offers a range of services including web design and development, portfolio building, branding,
creative studio services, digital marketing, business strategy consulting, and AI automation.
The company operates multiple brands and platforms including NasHub, NDC Agency, Creative Studio, and Digital Growth Blueprint.
Nas Digital is based in the United Kingdom and serves clients worldwide.
The official website is https://nasdigital.uk
', 'nas digital, agency, company, business, uk, what is nas digital, about nas digital, services, digital agency');

        $this->addEntry('services', '
Nas Digital provides the following services:

1. Web Design and Development - Custom websites, landing pages, and web applications. Visit https://wealth-creation.nasdigital.uk/
2. NasHub Portfolio Builder - Build your personal portfolio website and host it live. Visit https://nashub.uk
3. Creative Studio Services - Premium design, development, and creative services for brands. Visit https://studio.nasdigital.uk/
4. Digital Growth Blueprint Training - Complete training system to build sustainable digital income streams. Visit https://www.nasdigitalgrowth.com/
5. Business Growth Strategy - Full-service digital agency for growth and strategy. Visit https://ndc-agency.co.uk/
6. Digital Marketing - Social media marketing, content creation, and digital advertising.
7. Branding - Personal and business branding services.
8. AI Automation - AI-powered solutions for business automation.
9. Digital Consulting - One-on-one consulting for business strategy and digital growth.
', 'services, web design, development, portfolio, branding, digital marketing, consulting, training, what services, offer, pricing, cost');

        $this->addEntry('portfolio_builder', '
NasHub is a portfolio builder platform available at https://nashub.uk
It allows you to build your personal portfolio website and host it live at your own link.
NasHub helps professionals, freelancers, and businesses showcase their work online.
The platform is designed to be easy to use and quick to set up.
For pricing and features, visit https://nashub.uk
', 'nashub, portfolio, build portfolio, personal website, showcase, hosting');

        $this->addEntry('training_program', '
The Digital Growth Blueprint is a complete training system by Mr. Nas available at https://www.nasdigitalgrowth.com/
It teaches you how to build sustainable digital income streams from scratch.
The program covers digital business fundamentals, content creation, marketing strategies, and scaling techniques.
It is designed for beginners and intermediate entrepreneurs who want to earn online.
The training includes actionable steps, real-world examples, and ongoing support.
', 'digital growth blueprint, training, program, course, learn, earn online, digital income, online business');

        $this->addEntry('contact', '
You can contact Mr. Nas and the Nas Digital team through the following channels:

- Telegram: https://t.me/NASDITALGROWTH (Best for quick support and updates)
- Email: contact@nasdigital.uk (For business inquiries and partnerships)
- YouTube: https://www.youtube.com/@Nasdigitalgrowth (Free training videos)
- Facebook: https://www.facebook.com/NasDigitalGrowth (Community updates)
- Google Business Profile for Nas Digital Consultancy Ltd

For consultations or business inquiries, the quickest way to get a response is through Telegram.
', 'contact, email, telegram, phone, reach, message, get in touch, support, consultation');

        $this->addEntry('pricing', '
For pricing inquiries, please visit the specific service websites:

- Website Development: https://wealth-creation.nasdigital.uk/
- NasHub Portfolio: https://nashub.uk
- Creative Studio: https://studio.nasdigital.uk/
- Digital Growth Blueprint: https://www.nasdigitalgrowth.com/
- Agency Services: https://ndc-agency.co.uk/

For custom pricing or packages, contact via Telegram at https://t.me/NASDITALGROWTH or email contact@nasdigital.uk
', 'pricing, cost, price, how much, fees, packages, plans, rates, payment');

        $this->addEntry('location', '
Nas Digital is based in the United Kingdom.
Nasir Digital Consultancy Ltd UK is a registered company in the UK.
Mr. Nas operates internationally and serves clients worldwide.
The business primarily works remotely with international clients.
', 'location, address, office, where, based, uk, london, international, remote');

        $this->addEntry('business_hours', '
Nas Digital operates with flexible business hours to serve international clients.
For the quickest response, reach out via Telegram at https://t.me/NASDITALGROWTH
Response times are typically within a few hours during business days.
For urgent inquiries, Telegram is the recommended channel.
', 'hours, business hours, working hours, when, availability, response time');

        $this->addEntry('nashub', '
NasHub is a portfolio builder platform available at https://nashub.uk
It is designed for professionals, freelancers, and businesses to create their online presence.
Features include customizable templates, live hosting, and personal domain support.
This is part of the Nas Digital ecosystem of digital tools and services.
', 'nashub, portfolio builder, personal website, showcase, what is nashub');

        $this->addEntry('ndc_agency', '
NDC Agency (Nas Digital Consultancy Agency) is the full-service digital agency arm of Nas Digital.
Available at https://ndc-agency.co.uk/
It specializes in business growth strategy, digital transformation, and marketing solutions.
NDC Agency serves businesses looking to scale their digital presence.
', 'ndc agency, agency, consultancy, business growth, strategy, full service, ndc');

        $this->addEntry('creative_studio', '
The Creative Studio by Nas Digital offers premium design and creative services.
Available at https://studio.nasdigital.uk/
Services include graphic design, video editing, brand identity, and visual content creation.
It serves both individuals and businesses who need professional creative work.
', 'creative studio, design, creative, studio, graphics, video, brand identity, premium design');

        $this->addEntry('stake_by_nas', '
Stake By Nas is an investment and staking platform available at https://stake.nasdigital.uk/
It is described as the smarter way to invest in real estate.
This is part of the Nas Digital ecosystem of digital businesses.
', 'stake, staking, investment, real estate, stake by nas, invest');

        $this->addEntry('growth_blog', '
The Growth Blog by Nas Digital is available at https://nasdigitalgrowth.co.uk/
It features articles and insights about digital systems, marketing, and business growth.
Content is written to provide actionable advice for entrepreneurs and business owners.
', 'blog, articles, growth blog, insights, digital systems, marketing tips');

        $this->addEntry('youtube', '
Mr. Nas runs a YouTube channel at https://www.youtube.com/@Nasdigitalgrowth
The channel features weekly videos on digital growth strategies, case studies, and actionable tips.
It is a free resource for anyone looking to learn about digital business and marketing.
', 'youtube, videos, channel, free training, video tutorials, subscribe');

        $this->addEntry('online_business', '
Mr. Nas teaches people how to start and grow online businesses.
The Digital Growth Blueprint at https://www.nasdigitalgrowth.com/ is his main training program.
He covers topics like:
- How to start an online business from scratch
- Digital marketing strategies
- Content creation and personal branding
- Building multiple income streams
- AI automation for business
- Website development and portfolio building

The best place to start is the Digital Growth Blueprint or his free YouTube content.
', 'online business, start business, earn online, make money, digital income, passive income, side hustle, entrepreneurship');

        $this->addEntry('ai_automation', '
Nas Digital offers AI automation services to help businesses streamline their operations.
This includes chatbots, workflow automation, AI-powered content creation, and process optimization.
Mr. Nas believes AI is a key tool for modern business growth and efficiency.
', 'ai, automation, chatbot, artificial intelligence, workflow, process automation, ai automation');

        $this->addEntry('consultation', '
Mr. Nas offers business consultation services.
For consultations, you can:
1. Send a message via Telegram at https://t.me/NASDITALGROWTH
2. Email contact@nasdigital.uk
3. Reach out through any of the social media channels

Consultations cover business strategy, digital growth, personal branding, and online business setup.
', 'consultation, book consultation, meeting, call, talk, strategy session, one on one');

        $this->addEntry('company_info', '
Nasir Digital Consultancy Ltd UK is the registered company behind Nas Digital.
Company Type: Private Limited Company
Jurisdiction: United Kingdom
Mr. Nas (Nasir Uddin) is the Founder and Director.
The company operates multiple brands: Nas Digital, NasHub, NDC Agency, Creative Studio, Stake By Nas, and Digital Growth Blueprint.
', 'company, registered, limited, uk company, legal, official, nasir digital consultancy');
    }
}
