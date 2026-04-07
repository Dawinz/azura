class Message {
  final String id;
  final String from;
  final String avatar;
  final String text;
  final DateTime dateTime;
  final bool isSender;

  Message({
    required this.id,
    required this.from,
    required this.avatar,
    required this.text,
    required this.dateTime,
    required this.isSender,
  });

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: json['id'],
      from: json['from'],
      avatar: json['avatar'],
      text: json['text'],
      dateTime: DateTime.parse(json['datetime']),
      isSender: json['is_sender'],
    );
  }
}
