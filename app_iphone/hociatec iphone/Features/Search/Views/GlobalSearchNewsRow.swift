import SwiftUI

struct GlobalSearchNewsRow: View {
    let article: NewsArticle
    var showsTitle: Bool = true

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if showsTitle {
                Text(article.title)
                    .fontWeight(.semibold)
                    .accessibilityAddTraits(.isHeader)
            }
            Text(article.excerpt)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(3)
        }
        .accessibilityElement(children: .contain)
    }
}
