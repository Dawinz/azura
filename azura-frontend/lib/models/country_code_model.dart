class CountryCode {
  final String code;
  final String name;

  CountryCode({required this.code, required this.name});
}

final List<CountryCode> countryCodes = [
  CountryCode(code: '+1', name: 'USA'),
  CountryCode(code: '+44', name: 'UK'),
  CountryCode(code: '+255', name: 'Tanzania'),
  // Add more country codes here
];
