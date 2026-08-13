import Foundation
import UniformTypeIdentifiers

func loadSupportMultipartFiles(from urls: [URL]) async -> [MultipartUploadFile] {
    await withTaskGroup(of: MultipartUploadFile?.self) { group in
        for url in urls {
            group.addTask {
                let accessed = url.startAccessingSecurityScopedResource()
                defer {
                    if accessed {
                        url.stopAccessingSecurityScopedResource()
                    }
                }

                do {
                    let data = try Data(contentsOf: url)
                    let type = UTType(filenameExtension: url.pathExtension) ?? .data
                    return MultipartUploadFile(
                        fieldName: "attachments",
                        filename: url.lastPathComponent,
                        mimeType: type.preferredMIMEType ?? "application/octet-stream",
                        data: data
                    )
                } catch {
                    return nil
                }
            }
        }

        var files: [MultipartUploadFile] = []
        for await file in group {
            if let file {
                files.append(file)
            }
        }
        return files
    }
}
