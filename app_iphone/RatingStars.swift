import SwiftUI

struct RatingStars: View {
    @Binding var rating: Int
    var maximum: Int = 5
    var size: CGFloat = 24

    var body: some View {
        HStack(spacing: 8) {
            ForEach(1...maximum, id: \.self) { value in
                Button {
                    rating = value
                } label: {
                    Image(systemName: value <= rating ? "star.fill" : "star")
                        .resizable()
                        .scaledToFit()
                        .frame(width: size, height: size)
                        .foregroundStyle(value <= rating ? .yellow : .gray)
                        .accessibilityLabel("\(value) étoile\(value > 1 ? "s" : "") sur \(maximum)")
                }
                .buttonStyle(.plain)
                .accessibilityHint("Définir la note à \(value)")
            }
        }
        .accessibilityElement(children: .contain)
        .accessibilityLabel("Note: \(rating) sur \(maximum)")
    }
}

#Preview(traits: .sizeThatFitsLayout) {
    StatefulPreviewWrapper(3) { value in
        RatingStars(rating: value)
            .padding()
    }
}

// A small helper to preview bindings
struct StatefulPreviewWrapper<Value, Content: View>: View {
    @State var value: Value
    var content: (Binding<Value>) -> Content
    init(_ initialValue: Value, content: @escaping (Binding<Value>) -> Content) {
        _value = State(wrappedValue: initialValue)
        self.content = content
    }
    var body: some View { content($value) }
}
