import SwiftUI

struct ShimmerRow: View {
    var body: some View {
        HStack(spacing: 12) {
            ShimmerView().frame(width: 64, height: 64)
            VStack(alignment: .leading, spacing: 8) {
                ShimmerView().frame(height: 14)
                ShimmerView().frame(height: 10)
                ShimmerView().frame(height: 10)
            }
        }
    }
}

struct ShimmerTile: View {
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            ShimmerView().frame(height: 140).cornerRadius(10)
            ShimmerView().frame(height: 12)
            ShimmerView().frame(height: 10)
        }
    }
}

private struct ShimmerView: View {
    @State private var phase: CGFloat = 0

    var body: some View {
        RoundedRectangle(cornerRadius: 8)
            .fill(
                LinearGradient(
                    gradient: Gradient(colors: [Color.gray.opacity(0.15), Color.gray.opacity(0.05), Color.gray.opacity(0.15)]),
                    startPoint: .leading,
                    endPoint: .trailing
                )
            )
            .mask(
                Rectangle()
                    .fill(
                        LinearGradient(
                            gradient: Gradient(colors: [Color.black.opacity(0.4), Color.black, Color.black.opacity(0.4)]),
                            startPoint: .leading,
                            endPoint: .trailing
                        )
                    )
                    .offset(x: phase)
            )
            .onAppear {
                withAnimation(.linear(duration: 1.2).repeatForever(autoreverses: false)) {
                    phase = 180
                }
            }
    }
}
