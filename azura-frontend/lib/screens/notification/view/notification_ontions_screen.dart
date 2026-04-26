import 'package:flutter/material.dart';
import 'package:shop/route/route_constants.dart';

class NotificationOptionsScreen extends StatelessWidget {
  const NotificationOptionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notification Options')),
      body: ListView(
        children: [
          ListTile(
            leading: const Icon(Icons.notifications_active_outlined),
            title: const Text('Push notifications'),
            subtitle: const Text('Manage push notification preferences'),
            onTap: () {
              Navigator.pushNamed(context, enableNotificationScreenRoute);
            },
          ),
          const ListTile(
            leading: Icon(Icons.mark_email_unread_outlined),
            title: Text('Email notifications'),
            subtitle: Text('Receive updates by email'),
            trailing: Text('Enabled'),
          ),
        ],
      ),
    );
  }
}
