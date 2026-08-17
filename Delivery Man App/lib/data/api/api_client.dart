import 'dart:convert';
import 'dart:developer';
import 'dart:io';
import 'dart:typed_data';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart' as foundation;
import 'package:path/path.dart';
import 'package:http_parser/http_parser.dart';
import 'package:sixvalley_delivery_boy/data/models/response/error_response.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class ApiClient extends GetxService {
  final String appBaseUrl;
  final SharedPreferences sharedPreferences;
  final FlutterSecureStorage secureStorage;
  static const String noInternetMessage = 'Connection to API server failed due to internet connection';
  final int timeoutInSeconds = 30;

  String? token;
  late Map<String, String>? _mainHeaders;

  ApiClient({required this.appBaseUrl, required this.sharedPreferences, required this.secureStorage, String? token}) {
    this.token = token ?? sharedPreferences.getString(AppConstants.token);
    debugPrint('Token: ${this.token}');

    updateHeader(this.token, sharedPreferences.getString(AppConstants.languageCode));
    _loadSecureToken();
  }

  Future<void> _loadSecureToken() async {
    String? secureToken = await secureStorage.read(key: AppConstants.token);
    if (secureToken != null) {
      token = secureToken;
      updateHeader(token, sharedPreferences.getString(AppConstants.languageCode));
    }
  }

  void updateHeader(String? token, String? languageCode) {
    _mainHeaders = {
      'Content-Type': 'application/json; charset=UTF-8',
      AppConstants.localizationKey: languageCode ?? AppConstants.languages[0].languageCode ?? 'en',
      'Authorization': 'Bearer $token'
    };
  }

  Future<Response> getData(String uri, {Map<String, dynamic>? query, Map<String, String>? headers}) async {
    try {
      debugPrint('====> API Call: $uri\nHeader: $_mainHeaders');
      http.Response _response = await http.get(
        Uri.parse(appBaseUrl+uri),
        headers: headers ?? _mainHeaders,
      ).timeout(Duration(seconds: timeoutInSeconds));
      return handleResponse(_response, uri);
    } catch (e) {
      return const Response(statusCode: 1, statusText: noInternetMessage);
    }
  }

  Future<Response> postData(String uri, dynamic body, {Map<String, String>? headers}) async {
    try {
      debugPrint('====> API Call: $uri\nHeader: $_mainHeaders');
      debugPrint('====> API Body: $body');
      http.Response _response = await http.post(
        Uri.parse(appBaseUrl+uri),
        body: jsonEncode(body),
        headers: headers ?? _mainHeaders,
      ).timeout(Duration(seconds: timeoutInSeconds));
      return handleResponse(_response, uri);
    } catch (e) {
      return const Response(statusCode: 1, statusText: noInternetMessage);
    }
  }

  Future<Response> postMultipartData(String uri, Map<String, String> body, List<MultipartBody> multipartBody, {Map<String, String>? headers, List<PlatformFile>? platformFile}) async {
    try {
      debugPrint('====> API Call: $uri\nHeader: $_mainHeaders');
      debugPrint('====> API Body: $body with ${multipartBody.length} files');
      http.MultipartRequest _request = http.MultipartRequest('POST', Uri.parse(appBaseUrl+uri));
      _request.headers.addAll(headers ?? _mainHeaders as Map<String, String>);
      for(MultipartBody multipart in multipartBody) {
        if(foundation.kIsWeb) {
          Uint8List _list = await multipart.file.readAsBytes();
          http.MultipartFile _part = http.MultipartFile(
            multipart.key, multipart.file.readAsBytes().asStream(), _list.length,
            filename: basename(multipart.file.path), contentType: MediaType('image', 'jpg'),
          );
          _request.files.add(_part);
        }else {
          File _file = File(multipart.file.path);
          final fileName = _file.path.split(RegExp(r'[/\\]')).last;
          _request.files.add(http.MultipartFile.fromBytes(
            multipart.key, _file.readAsBytesSync(), filename: fileName,
          ));
        }
      }

      if(platformFile != null && platformFile.isNotEmpty) {
        for(PlatformFile pfile in platformFile) {
          if (pfile.readStream != null) {
            _request.files.add(http.MultipartFile('file[]', pfile.readStream!, pfile.size, filename: basename(pfile.name)));
          } else if (pfile.bytes != null) {
            _request.files.add(http.MultipartFile.fromBytes('file[]', pfile.bytes!, filename: basename(pfile.name)));
          } else if (pfile.path != null) {
            File f = File(pfile.path!);
            _request.files.add(http.MultipartFile.fromBytes('file[]', f.readAsBytesSync(), filename: basename(pfile.name)));
          }
        }
      }

      _request.fields.addAll(body);
      http.Response _response = await http.Response.fromStream(await _request.send());
      return handleResponse(_response, uri);
    } catch (e) {
      return const Response(statusCode: 1, statusText: noInternetMessage);
    }
  }

  Future<Response> putData(String uri, dynamic body, {Map<String, String>? headers}) async {
    try {
      debugPrint('====> API Call: $uri\nHeader: $_mainHeaders');
      debugPrint('====> API Body: $body');
      http.Response _response = await http.put(
        Uri.parse(appBaseUrl+uri),
        body: jsonEncode(body),
        headers: headers ?? _mainHeaders,
      ).timeout(Duration(seconds: timeoutInSeconds));
      return handleResponse(_response, uri);
    } catch (e) {
      return const Response(statusCode: 1, statusText: noInternetMessage);
    }
  }

  Future<Response> deleteData(String uri, {Map<String, String>? headers}) async {
    try {
      debugPrint('====> API Call: $uri\nHeader: $_mainHeaders');
      http.Response _response = await http.delete(
        Uri.parse(appBaseUrl+uri),
        headers: headers ?? _mainHeaders,
      ).timeout(Duration(seconds: timeoutInSeconds));
      return handleResponse(_response, uri);
    } catch (e) {
      return const Response(statusCode: 1, statusText: noInternetMessage);
    }
  }

  Response handleResponse(http.Response response, String uri) {

    dynamic _body;
    try {
      _body = jsonDecode(response.body);
    }catch(e) {
      debugPrint(e.toString());
    }
    Response _response = Response(
      body: _body ?? response.body, bodyString: response.body.toString(),
      headers: response.headers, statusCode: response.statusCode, statusText: response.reasonPhrase,
    );
    if(_response.statusCode != 200 && _response.body != null && _response.body is !String) {
      if(_response.body.toString().startsWith('{errors: [{code:')) {
        ErrorResponse _errorResponse = ErrorResponse.fromJson(_response.body);
        _response = Response(statusCode: _response.statusCode, body: _response.body, statusText: _errorResponse.errors![0].message);
      }else if(_response.body.toString().startsWith('{message')) {
        _response = Response(statusCode: _response.statusCode, body: _response.body, statusText: _response.body['message']);
      }
    }else if(_response.statusCode != 200 && _response.body == null) {
      _response = const Response(statusCode: 0, statusText: noInternetMessage);
    }
    log('====> API Response: [${_response.statusCode}] $uri\n${_response.body}');
    return _response;
  }
}

class MultipartBody {
  String key;
  XFile file;

  MultipartBody(this.key, this.file);
}