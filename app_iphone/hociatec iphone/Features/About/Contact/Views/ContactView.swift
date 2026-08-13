import SwiftUI

struct ContactView: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: ContactViewModel
    private let initialSubject: String?

    init(service: ContactServing, initialSubject: String? = nil) {
        self.initialSubject = initialSubject
        _viewModel = StateObject(
            wrappedValue: ContactViewModel(
                service: service,
                initialSubject: initialSubject
            )
        )
    }

    var body: some View {
        Form {
            ContactIdentitySection(viewModel: viewModel)
            ContactMessageSection(viewModel: viewModel)
            ContactSubmitSection(viewModel: viewModel)
            ContactFeedbackSection(success: viewModel.success, error: viewModel.error)
        }
        .formStyle(.grouped)
        .navigationTitle("Contact")
        .onAppear {
            if let profile = container.account.profile {
                viewModel.prefill(name: profile.fullName, email: profile.email)
            }
            viewModel.prefillSubject(initialSubject)
        }
    }
}
