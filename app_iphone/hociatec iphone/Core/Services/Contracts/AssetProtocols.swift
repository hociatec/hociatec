import Foundation

protocol AssetServing {
    func assetURL(for path: String?) -> URL?
}
