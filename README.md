# SAHARA reference data

Governed metadata and validators for the SAHARA application.

SAHARA's canonical Qur'an source is the King Fahd Glorious Qur'an Printing Complex developer platform. No alternate Qur'an text provider may be used without explicit project-owner approval.

The public package contains no Quran corpus, font binaries, translations or Tafsir text. `manifests/kfgqpc.json` pins the official Hafs v3 / developer update 15.0 archive and Tafsir Muyassar v3 archive, official checksums, selected XML entries, matching font hashes and non-text integrity fixtures.

Download only the exact official URLs in that manifest into private storage. `KfgqpcPackage::read()` verifies archive checksums before reading named entries, then validates 6,236 ordered canonical ayahs, 114 Surahs, 604 pages, 30 juz, names, Unicode, search fields and line bounds. It does not extract arbitrary archive paths or execute supplied SQL. Tafsir's embedded Quran quotations use its own supplied font; its older Quran text column never replaces the canonical Hafs text.

Run `composer test`. Set `KFGQPC_PACKAGE_DIR` to a private directory containing the two original ZIPs for official package integration checks. Without private archives those checks explicitly report SKIP. Public synthetic tests never claim to validate downloaded Quran text.

The developer platform offers resources for application development. Public standalone corpus redistribution has not been established. Full archives/XML/fonts remain in ignored private application storage; original font notices remain embedded and are extracted during controlled setup. See [official developer platform](https://qurancomplex.gov.sa/quran-dev/) and [policy](https://policy.qurancomplex.gov.sa/).

English/Bengali/Urdu/Hindi ayah-linked translations are NOT YET AVAILABLE in SAHARA: official publication pages exist, but a versioned, machine-readable ayah mapping has not been verified. No alternate provider is used.

The 0.4.0 correction removes the unapproved text and translation manifests added in 0.3.0 and independently transcribed Quran Dua excerpts. Normal Git history is retained.
