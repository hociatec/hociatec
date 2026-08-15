import Foundation

let newsDateFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .none
    return formatter
}()

func newsShareURL(for article: NewsArticle) -> URL {
    AppConfig.websiteURL(path: "/actualites/\(article.slug)")
}
