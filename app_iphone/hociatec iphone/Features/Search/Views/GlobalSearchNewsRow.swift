import SwiftUI

struct GlobalSearchNewsRow: View {
    let article: NewsArticle

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(article.title)
                .fontWeight(.semibold)
            Text(article.excerpt)
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(3)
            if let publishedAt = article.publishedAt {
                Text(DateFormatters.frDay.string(from: publishedAt))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
}
