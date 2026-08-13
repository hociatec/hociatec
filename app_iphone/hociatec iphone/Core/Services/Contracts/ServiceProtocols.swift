import Foundation

protocol AssetServing {
    func assetURL(for path: String?) -> URL?
}

protocol AccountServing {
    func profile() async throws -> UserProfile
    func login(email: String, password: String) async throws -> String
    func logout() async
    func updateProfile(
        firstName: String,
        lastName: String,
        email: String,
        address: String?,
        postalCode: String?,
        city: String?,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws -> UserProfile
    func deleteAccount() async throws
    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws
    func verifyAccount(token: String) async throws
    func requestPasswordReset(email: String) async throws
    func resetPassword(token: String, password: String, confirmPassword: String) async throws
    func createAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws
    func updateAddress(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws
    func deleteAddress(id: Int) async throws
    func setDefaultAddress(id: Int) async throws
    func listAddresses() async throws -> [UserAddress]
}

protocol CartServing {
    func fetchCart() async throws -> Cart
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart
    func removeFromCart(productId: Int) async throws -> Cart
    func clearCart() async throws -> Cart
    func checkout() async throws -> OrderSummary
}

protocol ProductServing: AssetServing {
    func featuredProducts() async throws -> [Product]
    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product]
    func categories() async throws -> [CategorySummary]
    func product(slug: String) async throws -> Product
    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData
}

protocol FavoritesServing {
    func listFavorites() async throws -> [FavoriteEntry]
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse
    func removeFavorite(productId: Int) async throws -> Bool
}

protocol OrderServing {
    func myOrders() async throws -> [OrderSummary]
    func order(id: Int) async throws -> OrderSummary
    func cancelOrder(id: Int) async throws -> OrderSummary
    func pendingReviews() async throws -> [PendingReviewItem]
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review
}

protocol AppointmentServing {
    func appointmentPrestations() async throws -> [AppointmentPrestation]
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot]
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary
    func cancelAppointment(id: Int) async throws
    func myAppointments() async throws -> AppointmentList
}

protocol QuoteServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary
    func myQuotes() async throws -> [QuoteSummary]
    func myQuotePdf(id: Int) async throws -> Data
    func deleteQuote(id: Int) async throws
}

protocol NewsServing {
    func latestNews(limit: Int) async throws -> [NewsArticle]
    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData
    func newsArticle(slug: String) async throws -> NewsArticle
    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData
    func createNewsComment(slug: String, content: String) async throws -> NewsComment
}

protocol ServiceCatalogServing: AssetServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func publicService(id: Int) async throws -> QuoteService
}

protocol TrainingServing {
    func trainingCategories() async throws -> [TrainingCategory]
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData
    func training(slug: String) async throws -> TrainingDetailData
    func myEnrollments(page: Int, perPage: Int) async throws -> TrainingEnrollmentListData
}

protocol WorkspaceServing {
    func communicationPreferences() async throws -> CommunicationPreferencesData
    func updateCommunicationPreferences(preferences: [String]) async throws -> CommunicationPreferencesData
    func loyaltyBalance() async throws -> LoyaltyBalance
    func convertLoyalty(points: Int) async throws -> LoyaltyConversionData
}

protocol SupportServing {
    func mySupportRequests(page: Int, perPage: Int) async throws -> SupportRequestListData
    func mySupportRequest(id: Int) async throws -> SupportRequestSummary
    func createSupportRequest(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary
    func replySupportRequest(id: Int, subject: String?, message: String, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary
    func mySupportAttachment(id: Int, name: String) async throws -> Data
}

protocol VoucherServing {
    func myVouchers(page: Int, perPage: Int) async throws -> VoucherListData
}

protocol TradeInServing {
    func tradeInMetadata() async throws -> TradeInMetadata
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInSummary
    func myTradeIns(page: Int, perPage: Int) async throws -> TradeInListData
    func myTradeInReceipt(id: Int) async throws -> Data
    func respondToTradeIn(id: Int, action: String) async throws
}

protocol ContactServing {
    func sendContact(name: String, email: String, subject: String, message: String) async throws
}

protocol AuditServing {
    func auditMetadata() async throws -> AuditMetadata
    func createAudit(type: String, url: String, objectives: String?) async throws -> AuditCreateResponse
    func myAudits(page: Int, perPage: Int) async throws -> AuditListData
    func myAudit(id: Int) async throws -> AuditDetail
    func myAuditPdf(id: Int) async throws -> Data
    func myAuditSummaryPdf(id: Int) async throws -> Data
}

protocol BetaServing {
    func betaProfileChoices() async throws -> [String: [BetaChoice]]
    func myBetaProfile() async throws -> BetaProfile?
    func updateMyBetaProfile(payload: [String: Any]) async throws -> BetaProfile
    func deleteMyBetaProfile() async throws
    func betaCampaigns() async throws -> [BetaCampaign]
    func myBetaReports(page: Int, perPage: Int) async throws -> BetaReportsData
    func myBetaReport(id: Int) async throws -> BetaBugReport
    func createBetaReport(payload: [String: String], screenshots: [MultipartUploadFile]) async throws
    func betaReportComments(id: Int, page: Int, perPage: Int) async throws -> BetaCommentsData
    func createBetaReportComment(id: Int, content: String) async throws -> BetaBugReportComment
}
