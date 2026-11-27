# Feature Two: SABAYRA AI Menu Creator & Khmer Translator

## 1. Capabilities

Accessible at **AI Center -> AI Menu Creator** (`GET /app/ai/menu-creator`, route `business.ai.menu-creator`), `App\Services\AI\MenuCreatorService` assists merchants in building bilingual Khmer & English catalogs:

1. **Write New Bilingual Description**: Produces natural `name_km`, `name_en`, `description_km`, `description_en`, and a menu highlight hook.
2. **Improve Existing Product Copy**: Refines existing catalog descriptions for clarity and appeal while keeping prices unchanged.
3. **Khmer <-> English Menu Translation**: Translates dish/product copy naturally for Cambodian hospitality and retail terminology.
4. **Combo & Pricing Suggestions**: Anchors recommendations directly to the store's top-selling items and active price band (`min_usd`, `avg_usd`, `max_usd`).

## 2. Explicit Human Review Workflow

- Generating AI copy creates a draft record in `ai_generated_contents` (`feature = 'menu_creator'`, `status = 'draft'`).
- Existing `products` rows are **never modified automatically** on generation.
- Merchants review the Khmer and English preview and click **Apply Selected Fields to Product** (`POST /app/ai/menu-creator/{content}/apply`) to update chosen fields (`name_en`, `name_km`, `description_en`, `description_km`) and mark the draft `applied`.
- Links directly to SABAYRA's bulk importer (`/app/ai-menu-builder`) for bulk CSV/menu creation.
