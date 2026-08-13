import SwiftUI
import UniformTypeIdentifiers
#if canImport(UIKit)
import UIKit
#endif

struct TradeInRequestView: View {
    @StateObject private var viewModel: TradeInViewModel
    @State private var showingFileImporter = false
    @Environment(\.dismiss) private var dismiss

    init(service: TradeInServing, account: AccountViewModel) {
        _viewModel = StateObject(wrappedValue: TradeInViewModel(service: service, account: account))
    }

    var body: some View {
        Form {
            TradeInStatusSection(error: viewModel.error, successMessage: viewModel.successMessage)
            TradeInProductSection(viewModel: viewModel)
            TradeInConditionSection(viewModel: viewModel)
            TradeInContactSection(viewModel: viewModel)
            TradeInRibSection(ribFileName: viewModel.ribFileName) {
                showingFileImporter = true
            }
            TradeInConsentSection(consent: $viewModel.consent)
            TradeInSubmitSection(isSubmitting: viewModel.isSubmitting) {
                await submitTradeIn()
            }
        }
        .navigationTitle("Reprise")
        .task { await viewModel.loadMetadata() }
        .fileImporter(
            isPresented: $showingFileImporter,
            allowedContentTypes: [.pdf],
            allowsMultipleSelection: false
        ) { result in
            handleFileImport(result)
        }
    }

    private func submitTradeIn() async {
        let ok = await viewModel.submit()
        if ok {
#if canImport(UIKit)
            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
            dismiss()
        }
    }

    private func handleFileImport(_ result: Result<[URL], Error>) {
        switch result {
        case let .success(urls):
            guard let url = urls.first else { return }
            let accessed = url.startAccessingSecurityScopedResource()
            defer {
                if accessed {
                    url.stopAccessingSecurityScopedResource()
                }
            }

            do {
                let data = try Data(contentsOf: url)
                let fileName = url.lastPathComponent.isEmpty ? "rib.pdf" : url.lastPathComponent
                viewModel.setRib(fileName: fileName, data: data)
            } catch {
                viewModel.error = "Impossible de lire le PDF sélectionné."
            }
        case .failure:
            viewModel.error = "Sélection du PDF annulée ou invalide."
        }
    }
}
