import Foundation
import Combine

@MainActor
final class AccountViewModel: ObservableObject {
    static let birthDateFormatter: DateFormatter = {
        let df = DateFormatter()
        df.calendar = Calendar(identifier: .iso8601)
        df.locale = Locale(identifier: "en_US_POSIX")
        df.timeZone = TimeZone(secondsFromGMT: 0)
        df.dateFormat = "yyyy-MM-dd"
        return df
    }()

    @Published var email = ""
    @Published var password = ""
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?
    @Published var globalDialog: FeedbackDialogState?
    @Published var profile: UserProfile?

    @Published var firstName: String = ""
    @Published var lastName: String = ""
    @Published var address: String? = nil
    @Published var postalCode: String? = nil
    @Published var city: String? = nil
    @Published var birthDate: String = ""
    @Published var phoneNumber: String = ""
    @Published var gender: String = "autre"
    @Published var roles: [String] = []
    @Published var addresses: [UserAddress] = []
    @Published var isLoggedIn: Bool
    @Published var rememberSession: Bool

    let useCases: AccountUseCases
    let session: SessionStore
    var cancellables = Set<AnyCancellable>()
    var profileRequestID = 0
    var addressesRequestID = 0
    var addressMutationRequestID = 0

    init(useCases: AccountUseCases, session: SessionStore) {
        self.useCases = useCases
        self.session = session
        self.isLoggedIn = session.jwtToken != nil
        self.rememberSession = session.rememberSession
        self.profile = session.profile
        self.email = session.profile?.email ?? session.loginEmail ?? ""
        if let p = session.profile {
            self.firstName = p.firstName
            self.lastName = p.lastName
            self.address = p.address
            self.postalCode = p.postalCode
            self.city = p.city
            self.birthDate = normalizedBirthDate(p.birthDate)
            self.phoneNumber = p.phoneNumber
            self.gender = normalizedGender(p.gender ?? "autre")
            self.roles = p.roles
            self.addresses = p.addresses ?? []
        } else {
            self.gender = "autre"
            self.addresses = []
        }
        bindSession()
    }
}
