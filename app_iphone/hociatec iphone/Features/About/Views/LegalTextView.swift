import SwiftUI

struct LegalTextView: View {
    let title: String
    let updatedAt: String
    let sections: [LegalSection]

    var body: some View {
        List {
            Section {
                LabeledContent("Dernière mise à jour", value: updatedAt)
            }

            ForEach(sections) { section in
                Section(section.title) {
                    ForEach(Array(section.paragraphs.enumerated()), id: \.offset) { _, paragraph in
                        Text(paragraph)
                    }
                }
            }
        }
        .navigationTitle(title)
    }
}
