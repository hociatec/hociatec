import Foundation
import Combine

@MainActor
final class AppNavigationState: ObservableObject {
    enum SheetRoute: Identifiable, Equatable {
        case resetPassword(token: String)
        case activateAccount(token: String)

        var id: String {
            switch self {
            case let .resetPassword(token):
                return "reset-password:\(token)"
            case let .activateAccount(token):
                return "activation:\(token)"
            }
        }
    }

    @Published var presentedSheet: SheetRoute?

    func handle(url: URL) {
        guard let route = route(from: url) else { return }
        presentedSheet = route
    }

    func dismissSheet() {
        presentedSheet = nil
    }

    private func route(from url: URL) -> SheetRoute? {
        let components = url.pathComponents.filter { $0 != "/" }

        if let token = token(
            for: "reset-password",
            pathComponents: components,
            url: url
        ) {
            return .resetPassword(token: token)
        }

        if let token = token(
            for: "activation",
            pathComponents: components,
            url: url
        ) {
            return .activateAccount(token: token)
        }

        return nil
    }

    private func token(for route: String, pathComponents: [String], url: URL) -> String? {
        if let index = pathComponents.firstIndex(of: route), index + 1 < pathComponents.count {
            let token = pathComponents[index + 1].removingPercentEncoding ?? pathComponents[index + 1]
            return token.isEmpty ? nil : token
        }

        guard
            let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
            let routeValue = components.queryItems?.first(where: { $0.name == "route" })?.value,
            routeValue == route,
            let tokenValue = components.queryItems?.first(where: { $0.name == "token" })?.value,
            !tokenValue.isEmpty
        else {
            return nil
        }

        return tokenValue
    }
}
