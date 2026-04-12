# Codemagic: Apple / App Store release guide (Flutter)

Use this checklist for **every** Flutter app you ship to **Apple App Store / TestFlight** using **Codemagic**. It incorporates fixes from real deployment issues (missing uploads, wrong integration names, branding, and broken Android resources).

*(Google Play is out of scope here — use your own process for Android releases.)*

---

## 1. Naming convention: Apple key in Codemagic (use everywhere)

In **Codemagic → Settings → Integrations → Apple Developer Portal**, the App Store Connect API key is registered under a **label you choose** (not Apple’s Key ID).

**Standard name to use for all your apps:** `applestoreconnectkey`

- In **`codemagic.yaml`**, under your workflow, the value must match **exactly** (case-sensitive):

```yaml
    integrations:
      app_store_connect: applestoreconnectkey
```

- If the label in Codemagic is different, either **rename the integration** in Codemagic to `applestoreconnectkey` or change this line to match. A mismatch produces **no upload** to Apple (or cryptic auth errors).

**Apple side:** create the key in [App Store Connect → Users and Access → Integrations → App Store Connect API](https://appstoreconnect.apple.com/access/integrations/api) with **App Manager** (or Admin). Download the `.p8` once. Official setup: [Codemagic – App Store Connect (Flutter editor)](https://docs.codemagic.io/flutter-publishing/publishing-to-app-store/) for keys/integration; for YAML publishing use [App Store Connect with codemagic.yaml](https://docs.codemagic.io/yaml-publishing/app-store-connect/).

---

## 2. Critical lesson: a green Codemagic build ≠ App Store upload

| Symptom | Likely cause |
|--------|----------------|
| Build succeeds, **TestFlight empty** | **No `publishing:` to App Store Connect** — only `artifacts:` saves the IPA on Codemagic; Apple never receives it. |
| **“Publishing artifact … ipa”** only | Same: add **`publishing.app_store_connect`** (see below). |
| **Add for Review** says choose a build | Upload may be missing, or build still **Processing** in TestFlight (wait 15–60+ minutes). |

**Required pattern in `codemagic.yaml` (iOS):**

```yaml
    integrations:
      app_store_connect: applestoreconnectkey

    # … scripts that produce build/ios/ipa/*.ipa …

    artifacts:
      - build/ios/ipa/*.ipa

    publishing:
      app_store_connect:
        auth: integration
```

Optional: `submit_to_testflight: true`, `beta_groups: [...]` — see Codemagic docs.

**Workflow Editor vs YAML:** If the app uses **`codemagic.yaml`**, turning on “Distribution → App Store Connect” in the UI is **not** a substitute for the **`publishing:`** block above. The YAML file controls uploads when that workflow is selected.

---

## 3. Flutter project checklist (every app)

### Identity (iOS / App Store Connect)

- **`PRODUCT_BUNDLE_IDENTIFIER`** in Xcode / `ios/Runner.xcodeproj/project.pbxproj` must match the App Store Connect app record **exactly**.

### Versioning

- **`pubspec.yaml`:** `version: x.y.z+build` — each upload needs a **higher build number** (`+build`) than the last binary Apple accepted.

### Secrets / config at build time

- If you use **`--dart-define`** (e.g. Supabase URL/keys), add the same defines in **Codemagic** workflow environment or script so **release** builds are not shipped with empty config.

### Icons and splash (no default Flutter branding)

- Add **`flutter_launcher_icons`** with a **1024×1024** source (e.g. `assets/branding/app_icon.png`), then run `dart run flutter_launcher_icons`.
- Add **`flutter_native_splash`** in **`dependencies`** and call **`FlutterNativeSplash.preserve` / `remove()`** so launch screens dismiss correctly.
- **Web:** after regenerating splash, ensure **`web/index.html`** still includes **`flutter_bootstrap.js`** and normal `<head>` tags — a bad merge can “succeed” at build but break the web loader.
- **Android:** `android/app/src/main/res/values/styles.xml` and **`values-night/styles.xml`** must be **valid UTF-8** XML. Corrupt/binary files break tools (e.g. `flutter_native_splash`) and local Android builds.

### iOS – encryption / export compliance

- For apps that only use **standard HTTPS** (typical APIs), add to **`ios/Runner/Info.plist`**:

```xml
<key>ITSAppUsesNonExemptEncryption</key>
<false/>
```

- Complete **App Store Connect → encryption** questions accurately; App Purpose / algorithm steps must match your app.

### App Store listing

- **Privacy policy URL** (public `https://…`).
- Screenshots, descriptions, **App Privacy**, copyright, and other required metadata.

---

## 4. Codemagic app settings

- **Use configuration from repository** when you intend to drive the pipeline from **`codemagic.yaml`**.
- If logs show **Shorebird** (or another tool) but your YAML does not, the **UI workflow** may still be active — open the app in Codemagic and ensure the workflow uses **codemagic.yaml**.

---

## 5. Quick verification after a build

1. **App Store Connect → TestFlight** — build appears (**Processing**, then **Ready**). Then **Distribution → version → Build** can select it.
2. Open the Codemagic log: confirm an **App Store Connect publish/upload** step, not only **artifact** upload to Codemagic.

---

## 6. Copy-paste reference: integration name

| Item | Value |
|------|--------|
| Codemagic Apple integration label (all apps) | **`applestoreconnectkey`** |
| YAML | `app_store_connect: applestoreconnectkey` |

---

*Adjust bundle IDs and workflow names per app.*
