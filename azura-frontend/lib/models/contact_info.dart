class ContactInfo {
  final String phone;
  final String address;
  final int countryId;
  final int stateId;
  final int cityId;

  ContactInfo({
    required this.phone,
    required this.address,
    required this.countryId,
    required this.stateId,
    required this.cityId,
  });

  factory ContactInfo.fromJson(Map<String, dynamic> json) {
    return ContactInfo(
      phone: json['phone'],
      address: json['address'],
      countryId: json['country_id'],
      stateId: json['state_id'],
      cityId: json['city_id'],
    );
  }
}
