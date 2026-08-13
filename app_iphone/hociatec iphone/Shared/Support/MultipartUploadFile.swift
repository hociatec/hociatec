import Foundation

struct MultipartUploadFile {
    let fieldName: String
    let filename: String
    let mimeType: String
    let data: Data
}
