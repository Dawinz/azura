class Conversation {
  final String id;
  final String subject;
  final String initialMessage;
  final String lastMessage;
  final String avatar;
  final String from;
  final int unread;
  final DateTime dateTime;

  Conversation({
    required this.id,
    required this.subject,
    required this.initialMessage,
    required this.lastMessage,
    required this.avatar,
    required this.from,
    required this.unread,
    required this.dateTime,
  });

  factory Conversation.fromJson(Map<String, dynamic> json) {
    return Conversation(
      id: json['id'],
      subject: json['subject'],
      initialMessage: json['initial_message'],
      lastMessage: json['last_message'],
      avatar: json['avatar'],
      from: json['from'],
      unread: json['unread'],
      dateTime: DateTime.parse(json['datetime']),
    );
  }
}
