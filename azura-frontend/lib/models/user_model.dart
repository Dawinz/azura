import 'dart:convert';

class UserModel {
  final String id;
  final String name;
  final String email;
  final String? avatar;
  final String? token;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.avatar,
    this.token,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'avatar': avatar,
      'token': token,
    };
  }

  factory UserModel.fromMap(Map<String, dynamic> map) {
    final name = map['name']?.toString();
    final username = map['username']?.toString();
    return UserModel(
      id: map['id']?.toString() ?? '',
      name: (name != null && name.isNotEmpty) ? name : (username ?? ''),
      email: map['email']?.toString() ?? '',
      avatar: map['avatar']?.toString(),
      token: map['token']?.toString(),
    );
  }

  String toJson() => json.encode(toMap());

  factory UserModel.fromJson(String source) =>
      UserModel.fromMap(json.decode(source));
}
