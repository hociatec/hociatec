import SwiftUI

struct AddressesErrorSection: View {
    let error: String?

    var body: some View {
        if let error {
            Text(error)
                .foregroundColor(.red)
        }
    }
}
