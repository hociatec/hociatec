# Repository Guidelines

## Project Structure

- `hociatec iphone/`: Main iOS app source (Swift/SwiftUI). Most screens, view models, and networking live here.
- `hociatec iphone.xcodeproj/`: Xcode project and build settings.
- `Assets.xcassets/`: App icons and image assets.
- `hociatec iphoneTests/`, `hociatec iphoneUITests/`: XCTest unit/UI tests.
- `src pour le dev/`: Backend reference code from production. Treat as **read-only** (use for understanding API contracts; don’t modify it in this repo).

## Build, Test, and Development Commands

- Build (Simulator): `xcodebuild -project "hociatec iphone.xcodeproj" -scheme "hociatec iphone" -destination 'generic/platform=iOS Simulator' build`
- Run locally: `open "hociatec iphone.xcodeproj"` then run the `hociatec iphone` scheme in Xcode.
- Tests: `xcodebuild -project "hociatec iphone.xcodeproj" -scheme "hociatec iphone" -destination 'platform=iOS Simulator,name=iPhone 15' test`

## Coding Style & Naming Conventions

- Swift 5 + SwiftUI; prefer `@MainActor` view models and `async/await` for API calls.
- Indentation: 4 spaces; keep views small by extracting subviews for repeated UI.
- Naming: types `PascalCase`, variables/functions `camelCase`, files match the primary type (e.g. `AccountScreen.swift`).
- Avoid force unwraps; handle nil/empty states explicitly (loading, error, empty lists).

## Testing Guidelines

- Framework: XCTest (`hociatec iphoneTests/`, `hociatec iphoneUITests/`).
- Name tests `SomethingTests.swift`; prefer testing view models and API decoding logic over SwiftUI layout.

## Commit & Pull Request Guidelines

- Git history is minimal; use clear, imperative commit subjects (e.g. “Fix appointment cancellation fallback”).
- PRs should include: a short summary, how to verify (steps/commands), and screenshots for UI changes.

## Configuration Notes

- API base URL is defined in `hociatec iphone/APIClient.swift`; keep changes backward-compatible with production endpoints.
