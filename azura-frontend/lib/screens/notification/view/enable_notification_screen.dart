import 'package:flutter/material.dart';

class EnableNotificationScreen extends StatefulWidget {
  const EnableNotificationScreen({super.key});

  @override
  State<EnableNotificationScreen> createState() =>
      _EnableNotificationScreenState();
}

class _EnableNotificationScreenState extends State<EnableNotificationScreen> {
  bool _enabled = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notification Settings')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Enable push notifications'),
              subtitle: const Text(
                  'Get order updates, offers, and important account alerts.'),
              value: _enabled,
              onChanged: (value) {
                setState(() {
                  _enabled = value;
                });
              },
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(_enabled
                        ? 'Notifications enabled'
                        : 'Notifications disabled'),
                  ),
                );
              },
              child: const Text('Save'),
            )
          ],
        ),
      ),
    );
  }
}
