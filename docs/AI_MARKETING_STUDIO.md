# Feature Three: SABAYRA AI Marketing Studio

## 1. Capabilities

Accessible at **AI Center -> AI Marketing Studio** (`GET /app/ai/marketing-studio`, route `business.ai.marketing`), `App\Services\AI\MarketingStudioService` generates localized promotional copy built for Cambodian digital channels:

- **Channels Supported**:
  - `facebook`: Cambodian Facebook Page promotional posts with local CTAs.
  - `tiktok`: Short-form TikTok / Reels video hooks, scene cues, voiceover, and CTAs.
  - `telegram`: Concise Telegram broadcast promotions for VIP/repeat customers.
  - `general`: In-store signage or flyer promotional copy.
- **Campaign Focuses**: `product_spotlight`, `combo_promo`, `holiday_khmer` (Khmer New Year, Pchum Ben, Water Festival), `flash_discount`, and `retention`.

## 2. Grounding & Management

- When linked to an active store `Product`, accurate effective USD (`$`) and KHR (`៛`) prices using the store's `exchange_rate_khr` are provided to the prompt.
- Generated campaigns store structured Khmer & English headlines, post bodies, video scripts, CTAs, hashtags, and suggested schedules in `ai_generated_contents` (`feature = 'marketing_studio'`).
- Merchants can copy Khmer copy, English copy, or delete drafts (`DELETE /app/ai/marketing-studio/{content}`) scoped strictly to their business.
