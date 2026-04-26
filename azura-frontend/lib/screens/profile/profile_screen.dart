import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<dynamic> _userFuture;

  @override
  void initState() {
    super.initState();
    _userFuture = ApiService.getProfile(1); // Replace with actual user ID
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Profile"),
      ),
      body: FutureBuilder<dynamic>(
        future: _userFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text("Failed to load profile: ${snapshot.error}"));
          }
          final data = snapshot.data;
          final Map<String, dynamic> user = (data is Map<String, dynamic>)
              ? data
              : <String, dynamic>{};
          final String name = (user['name'] ?? user['username'] ?? '').toString();
          final String email = (user['email'] ?? '').toString();
          return Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("Name: $name", style: const TextStyle(fontSize: 18)),
                const SizedBox(height: 8),
                Text("Email: $email", style: const TextStyle(fontSize: 18)),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    // Implement logout functionality
                  },
                  child: const Text("Logout"),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
