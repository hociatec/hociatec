import Foundation

extension AccountViewModel {
    func shouldIgnore(error: Error) -> Bool {
        error.isBenignCancellation
    }
}
