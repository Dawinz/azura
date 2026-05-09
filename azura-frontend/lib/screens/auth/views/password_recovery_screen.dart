import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';

import '../../../constants.dart';

class PasswordRecoveryScreen extends StatefulWidget {
  const PasswordRecoveryScreen({super.key});

  @override
  State<PasswordRecoveryScreen> createState() => _PasswordRecoveryScreenState();
}

class _PasswordRecoveryScreenState extends State<PasswordRecoveryScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Forgot password')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(defaultPadding),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Reset link',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: defaultPadding / 2),
                    const Text(
                      'Enter the email address for your account. If it exists in our system, '
                      'we will send reset instructions.',
                    ),
                    const SizedBox(height: defaultPadding * 2),
                    TextFormField(
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email address',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Enter your email';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: defaultPadding * 2),
                    ElevatedButton(
                      onPressed: _loading
                          ? null
                          : () async {
                              if (!_formKey.currentState!.validate()) return;
                              final messenger = ScaffoldMessenger.of(context);
                              final navigator = Navigator.of(context);
                              setState(() => _loading = true);
                              try {
                                await ApiService.forgotPassword(
                                  _emailController.text.trim(),
                                );
                                if (!mounted) return;
                                messenger.showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                      'If that email is registered, check your inbox for reset instructions.',
                                    ),
                                  ),
                                );
                                navigator.pop();
                              } catch (e) {
                                if (!mounted) return;
                                messenger.showSnackBar(
                                  SnackBar(content: Text(e.toString())),
                                );
                              } finally {
                                if (mounted) {
                                  setState(() => _loading = false);
                                }
                              }
                            },
                      child: _loading
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Send reset email'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
