import SwiftUI
import UniformTypeIdentifiers

struct CreateSupportRequestView: View {
    @Environment(\.dismiss) private var dismiss

    @ObservedObject var viewModel: SupportViewModel
    let orders: OrderServing

    @StateObject private var ordersViewModel: OrdersViewModel
    @State private var subject = ""
    @State private var reason = SupportReasons.other.rawValue
    @State private var message = ""
    @State private var selectedOrderId = ""
    @State private var attachmentURLs: [URL] = []
    @State private var showFileImporter = false

    init(viewModel: SupportViewModel, orders: OrderServing) {
        self.viewModel = viewModel
        self.orders = orders
        _ordersViewModel = StateObject(wrappedValue: OrdersViewModel(service: orders))
    }

    var body: some View {
        Form {
            requestFieldsSection
            submitSection
        }
        .navigationTitle("Nouvelle demande")
        .toolbar {
            ToolbarItem(placement: .cancellationAction) {
                Button("Fermer") { dismiss() }
            }
        }
        .task { await ordersViewModel.load(force: true) }
        .fileImporter(
            isPresented: $showFileImporter,
            allowedContentTypes: [.data, .pdf, .image],
            allowsMultipleSelection: true
        ) { result in
            if case let .success(urls) = result {
                attachmentURLs.append(contentsOf: urls)
            }
        }
    }

    private var requestFieldsSection: some View {
        Section {
            TextField("Sujet", text: $subject)
            Picker("Motif", selection: $reason) {
                ForEach(SupportReasons.allCases, id: \.rawValue) { value in
                    Text(value.label).tag(value.rawValue)
                }
            }
            Picker("Commande liée", selection: $selectedOrderId) {
                Text("Aucune").tag("")
                ForEach(ordersViewModel.orders) { order in
                    Text(order.number).tag(String(order.id))
                }
            }
            TextEditor(text: $message)
                .frame(minHeight: 150)
            Button("Ajouter des pièces jointes") {
                showFileImporter = true
            }
            if !attachmentURLs.isEmpty {
                ForEach(attachmentURLs, id: \.absoluteString) { url in
                    Text(url.lastPathComponent)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
    }

    private var submitSection: some View {
        Section {
            Button("Créer la demande") {
                Task {
                    let attachments = await loadSupportMultipartFiles(from: attachmentURLs)
                    let success = await viewModel.create(
                        subject: subject.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty ? "Demande SAV" : subject,
                        reason: reason,
                        message: message,
                        orderId: Int(selectedOrderId),
                        attachments: attachments
                    )
                    if success {
                        dismiss()
                    }
                }
            }
            .disabled(viewModel.isSubmitting || message.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
        }
    }
}
