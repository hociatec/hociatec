import Foundation

enum DeviceIdentityStore {
    private static let keychainService = "fr.hociatec.device"
    private static let keychainAccount = "primary-device-id"

    static func currentDeviceIdentifier() -> String {
        if let value = KeychainValueStore.read(service: keychainService, account: keychainAccount),
           isValid(value) {
            return value
        }

        let newValue = "ios.\(UUID().uuidString.lowercased())"
        KeychainValueStore.write(newValue, service: keychainService, account: keychainAccount)
        return newValue
    }

    private static func isValid(_ value: String) -> Bool {
        guard let regex = try? NSRegularExpression(pattern: "^[A-Za-z0-9._:-]{16,128}$") else {
            return false
        }

        let range = NSRange(location: 0, length: value.utf16.count)
        return regex.firstMatch(in: value, options: [], range: range) != nil
    }
}
