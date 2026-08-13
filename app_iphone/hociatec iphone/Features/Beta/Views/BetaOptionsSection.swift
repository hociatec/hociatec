import SwiftUI

struct BetaOptionsSection: View {
    let headerTitle: String
    let options: [BetaChoice]
    @Binding var selection: [String]

    var body: some View {
        Section(
            content: {
                if options.isEmpty {
                    Text("Aucune option disponible.")
                        .foregroundStyle(.secondary)
                } else {
                    BetaOptionsRows(
                        options: options,
                        selectedValues: Set(selection),
                        onToggle: toggle
                    )
                }
            },
            header: {
                Text(verbatim: headerTitle)
            }
        )
    }

    private func toggle(_ value: String) {
        if selection.contains(value) {
            selection.removeAll(where: { $0 == value })
        } else {
            selection.append(value)
        }
    }
}

private struct BetaOptionsRows: View {
    let options: [BetaChoice]
    let selectedValues: Set<String>
    let onToggle: (String) -> Void

    var body: some View {
        ForEach(options, id: \.id) { option in
            BetaOptionRow(
                option: option,
                isSelected: selectedValues.contains(option.value),
                onToggle: onToggle
            )
        }
    }
}

private struct BetaOptionRow: View {
    let option: BetaChoice
    let isSelected: Bool
    let onToggle: (String) -> Void

    var body: some View {
        Button {
            onToggle(option.value)
        } label: {
            HStack {
                Text(option.label)
                Spacer()
                if isSelected {
                    Image(systemName: "checkmark.circle.fill")
                        .foregroundStyle(.accent)
                }
            }
        }
        .buttonStyle(.plain)
    }
}
