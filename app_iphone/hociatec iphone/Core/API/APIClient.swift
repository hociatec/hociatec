import Foundation
import Combine

/// Client HTTP léger pour l’API hociatec.fr.
final class APIClient: ObservableObject {
    let baseURL = URL(string: "https://api.hociatec.fr")!
    let authenticatedSessionMarker = "__cookie_session__"

    let session: URLSession
    let decoder: JSONDecoder
    let sessionStore: SessionStore
    let isoFormatter: ISO8601DateFormatter

    init(sessionStore: SessionStore, session: URLSession = .shared) {
        self.sessionStore = sessionStore
        self.session = session

        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .iso8601
        self.decoder = decoder

        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime]
        formatter.timeZone = .init(secondsFromGMT: 0)
        self.isoFormatter = formatter
    }

    func assetURL(for path: String?) -> URL? {
        guard var path, !path.isEmpty else { return nil }

        if path.hasPrefix("http") {
            return URL(string: path)
        }

        if path.hasPrefix("/") {
            path.removeFirst()
        }

        return baseURL.appendingPathComponent(path)
    }
}
