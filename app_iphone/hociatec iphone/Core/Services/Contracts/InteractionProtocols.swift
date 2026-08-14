import Foundation

protocol AppointmentServing {
    func appointmentPrestations() async throws -> [AppointmentPrestation]
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot]
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary
    func rescheduleAppointment(id: Int, startAt: Date) async throws -> AppointmentSummary
    func cancelAppointment(id: Int) async throws
    func myAppointments() async throws -> AppointmentList
}

protocol ContactServing {
    func sendContact(name: String, email: String, subject: String, message: String) async throws
}

protocol SupportServing {
    func mySupportRequests(page: Int, perPage: Int) async throws -> SupportRequestListData
    func mySupportRequest(id: Int) async throws -> SupportRequestSummary
    func createSupportRequest(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary
    func replySupportRequest(id: Int, subject: String?, message: String, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary
    func mySupportAttachment(id: Int, name: String) async throws -> Data
}

protocol NewsServing {
    func latestNews(limit: Int) async throws -> [NewsArticle]
    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData
    func newsArticle(slug: String) async throws -> NewsArticle
    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData
    func createNewsComment(slug: String, content: String) async throws -> NewsComment
}
