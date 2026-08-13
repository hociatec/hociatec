import Foundation

struct TemporarySharedFile: Identifiable {
    let id = UUID()
    let url: URL
    let title: String
}

enum TemporarySharedFileFactory {
    static func create(data: Data, fileName: String) throws -> TemporarySharedFile {
        let safeName = fileName.replacingOccurrences(of: "/", with: "-")
        let url = FileManager.default.temporaryDirectory.appendingPathComponent(safeName)

        if FileManager.default.fileExists(atPath: url.path) {
            try FileManager.default.removeItem(at: url)
        }

        try data.write(to: url, options: .atomic)
        return TemporarySharedFile(url: url, title: safeName)
    }
}
