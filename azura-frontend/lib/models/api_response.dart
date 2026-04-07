class ApiResponse<T> {
  final bool status;
  final String message;
  final T? data;

  ApiResponse({
    required this.status,
    required this.message,
    this.data,
  });

  factory ApiResponse.fromJson(
      Map<String, dynamic> json, T Function(dynamic) fromJson) {
    return ApiResponse<T>(
      status: json['status'],
      message: json['message'],
      data: json['data'] != null ? fromJson(json['data']) : null,
    );
  }
}
