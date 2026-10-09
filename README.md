# SAHARA Reference Data
Versioned configuration, manifests and schemas; never a production database. Composer namespace Sahara\Reference exposes Catalog.
Run `composer test`. Data is authored configuration; uncertain external sources contain metadata only. No Quran source/font copied. No users, finance, notes, prayer histories, OAuth tokens, keys or DB dumps.

Every dataset needs the manifest schema fields, explicit rights/redistribution/caching status and review date. pending-confirmation never authorizes inclusion of external data. Changes require validation and a new version. Application installs pin an immutable package commit.
Version0.2.0 adds worshipMetadata() and duaExcerpts(): configurable category labels plus two short public-domain original Arabic Quran supplications. No modern translation, Tafsir, audio, font or complete Quran dataset is bundled. Source/rights notes accompany each dataset; English headings are authored labels.

## v0.3.0 Quran source
The original Tanzil Uthmani v1.1 XML and Quran structure metadata v1.0 are included verbatim under their source permissions. Catalog::quranFiles() fails closed on hash/permission mismatch. Source notices remain inside the files, and Git disables text conversion for these XML files. Preserve Tanzil attribution/link and notices in consuming applications; never change verse text. Tests verify114 suras,6236 ayat,604 page divisions and source Bismillah attributes. These are textual page divisions, not a bundled Mushaf font or image facsimile. Review https://tanzil.net/updates/ before a content release. No modern translation or Tafsir corpus is bundled.
