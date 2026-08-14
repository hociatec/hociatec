import Foundation

extension Error {
    var isBenignCancellation: Bool {
        if self is CancellationError {
            return true
        }

        if let apiError = self as? APIError,
           case let .transport(underlyingError) = apiError {
            return underlyingError.isBenignCancellation
        }

        let nsError = self as NSError
        if nsError.domain == NSURLErrorDomain && nsError.code == NSURLErrorCancelled {
            return true
        }

        if nsError.domain == NSCocoaErrorDomain && nsError.code == NSUserCancelledError {
            return true
        }

        return false
    }
}
