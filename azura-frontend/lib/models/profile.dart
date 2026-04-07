class Profile {
  final String id;
  final String username;
  final String? avatar;
  final String email;
  final String emailStatus;
  final String slug;
  final String sendEmailNewMessage;

  Profile({
    required this.id,
    required this.username,
    this.avatar,
    required this.email,
    required this.emailStatus,
    required this.slug,
    required this.sendEmailNewMessage,
  });

  factory Profile.fromJson(Map<String, dynamic> json) {
    return Profile(
      id: json['id'],
      username: json['username'],
      avatar: json['avatar'],
      email: json['email'],
      emailStatus: json['email_status'],
      slug: json['slug'],
      sendEmailNewMessage: json['send_email_new_message'],
    );
  }
}
