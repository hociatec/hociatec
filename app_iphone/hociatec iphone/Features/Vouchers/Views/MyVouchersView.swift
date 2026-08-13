import SwiftUI

struct MyVouchersView: View {
    @StateObject private var viewModel: VouchersViewModel

    init(service: VoucherServing) {
        _viewModel = StateObject(wrappedValue: VouchersViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section("Bons actifs") {
                let active = viewModel.items.filter { !$0.isExpired }
                if viewModel.isLoading && viewModel.items.isEmpty {
                    ProgressView("Chargement...")
                } else if active.isEmpty {
                    Text("Aucun bon actif.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(active) { voucher in
                        VoucherRow(voucher: voucher)
                    }
                }
            }

            Section("Bons passés") {
                let past = viewModel.items.filter(\.isExpired)
                if past.isEmpty {
                    Text("Aucun bon passé.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(past) { voucher in
                        VoucherRow(voucher: voucher)
                    }
                }
            }
        }
        .navigationTitle("Mes bons de réduction")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
    }
}

private struct VoucherRow: View {
    let voucher: VoucherListItem

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(voucher.name)
                    .fontWeight(.semibold)
                Spacer()
                Text(voucher.discountLabel)
                    .font(.footnote.weight(.semibold))
            }
            Text("Code \(voucher.code)")
                .font(.footnote)
            if let description = voucher.description, !description.isEmpty {
                Text(description)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(voucher.validityLabel)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
    }
}

private extension VoucherListItem {
    var isExpired: Bool {
        if !isActive {
            return true
        }
        guard let endsAt else { return false }
        return endsAt < Date()
    }

    var discountLabel: String {
        if discountType == "percent" {
            return "\(discountValue)%"
        }
        return PriceFormatter.format(cents: discountValue)
    }

    var validityLabel: String {
        if let endsAt {
            return "Valable jusqu’au \(DateFormatters.frDay.string(from: endsAt))"
        }
        return "Sans date de fin"
    }
}
