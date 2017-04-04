<?php
	
namespace Spectral\MandrillBundle;

/**
 * Message class
 *
 * Copyright (c) 2017 Spectral, USA
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author: Wade Wece <pogeybait4883@gmail.com>
 * @copyright: 2017 Spectral, USA
 */

class Message
{
	public function __construct(Mandrill $master)
	{
        $this->master = $master;
    }
    
    /**
     * Send a new transactional message through Mandrill
     * @param struct $message the information on the message to send
     *     - html string the full HTML content to be sent
     *     - text string optional full text content to be sent
     *     - subject string the message subject
     *     - from_email string the sender email address.
     *     - from_name string optional from name to be used
     *     - to array an array of recipient information.
     *         - to[] struct a single recipient's information.
     *             - email string the email address of the recipient
     *             - name string the optional display name to use for the recipient
     *             - type string the header type to use for the recipient, defaults to "to" if not provided
     *     - headers struct optional extra headers to add to the message (most headers are allowed)
     *     - important boolean whether or not this message is important, and should be delivered ahead of non-important messages
     *     - track_opens boolean whether or not to turn on open tracking for the message
     *     - track_clicks boolean whether or not to turn on click tracking for the message
     *     - auto_text boolean whether or not to automatically generate a text part for messages that are not given text
     *     - auto_html boolean whether or not to automatically generate an HTML part for messages that are not given HTML
     *     - inline_css boolean whether or not to automatically inline all CSS styles provided in the message HTML - only for HTML documents less than 256KB in size
     *     - url_strip_qs boolean whether or not to strip the query string from URLs when aggregating tracked URL data
     *     - preserve_recipients boolean whether or not to expose all recipients in to "To" header for each email
     *     - view_content_link boolean set to false to remove content logging for sensitive emails
     *     - bcc_address string an optional address to receive an exact copy of each recipient's email
     *     - tracking_domain string a custom domain to use for tracking opens and clicks instead of mandrillapp.com
     *     - signing_domain string a custom domain to use for SPF/DKIM signing instead of mandrill (for "via" or "on behalf of" in email clients)
     *     - return_path_domain string a custom domain to use for the messages's return-path
     *     - merge boolean whether to evaluate merge tags in the message. Will automatically be set to true if either merge_vars or global_merge_vars are provided.
     *     - merge_language string the merge tag language to use when evaluating merge tags, either mailchimp or handlebars
     *     - global_merge_vars array global merge variables to use for all recipients. You can override these per recipient.
     *         - global_merge_vars[] struct a single global merge variable
     *             - name string the global merge variable's name. Merge variable names are case-insensitive and may not start with _
     *             - content mixed the global merge variable's content
     *     - merge_vars array per-recipient merge variables, which override global merge variables with the same name.
     *         - merge_vars[] struct per-recipient merge variables
     *             - rcpt string the email address of the recipient that the merge variables should apply to
     *             - vars array the recipient's merge variables
     *                 - vars[] struct a single merge variable
     *                     - name string the merge variable's name. Merge variable names are case-insensitive and may not start with _
     *                     - content mixed the merge variable's content
     *     - tags array an array of string to tag the message with.  Stats are accumulated using tags, though we only store the first 100 we see, so this should not be unique or change frequently.  Tags should be 50 characters or less.  Any tags starting with an underscore are reserved for internal use and will cause errors.
     *         - tags[] string a single tag - must not start with an underscore
     *     - subaccount string the unique id of a subaccount for this message - must already exist or will fail with an error
     *     - google_analytics_domains array an array of strings indicating for which any matching URLs will automatically have Google Analytics parameters appended to their query string automatically.
     *     - google_analytics_campaign array|string optional string indicating the value to set for the utm_campaign tracking parameter. If this isn't provided the email's from address will be used instead.
     *     - metadata array metadata an associative array of user metadata. Mandrill will store this metadata and make it available for retrieval. In addition, you can select up to 10 metadata fields to index and make searchable using the Mandrill search api.
     *     - recipient_metadata array Per-recipient metadata that will override the global values specified in the metadata parameter.
     *         - recipient_metadata[] struct metadata for a single recipient
     *             - rcpt string the email address of the recipient that the metadata is associated with
     *             - values array an associated array containing the recipient's unique metadata. If a key exists in both the per-recipient metadata and the global metadata, the per-recipient metadata will be used.
     *     - attachments array an array of supported attachments to add to the message
     *         - attachments[] struct a single supported attachment
     *             - type string the MIME type of the attachment
     *             - name string the file name of the attachment
     *             - content string the content of the attachment as a base64-encoded string
     *     - images array an array of embedded images to add to the message
     *         - images[] struct a single embedded image
     *             - type string the MIME type of the image - must start with "image/"
     *             - name string the Content ID of the image - use <img src="cid:THIS_VALUE"> to reference the image in your HTML content
     *             - content string the content of the image as a base64-encoded string
     * @param boolean $async enable a background sending mode that is optimized for bulk sending. In async mode, messages/send will immediately return a status of "queued" for every recipient. To handle rejections when sending in async mode, set up a webhook for the 'reject' event. Defaults to false for messages with no more than 10 recipients; messages with more than 10 recipients are always sent asynchronously, regardless of the value of async.
     * @param string $ip_pool the name of the dedicated ip pool that should be used to send the message. If you do not have any dedicated IPs, this parameter has no effect. If you specify a pool that does not exist, your default pool will be used instead.
     * @param string $send_at when this message should be sent as a UTC timestamp in YYYY-MM-DD HH:MM:SS format. If you specify a time in the past, the message will be sent immediately. An additional fee applies for scheduled email, and this feature is only available to accounts with a positive balance.
     * @return array of structs for each recipient containing the key "email" with the email address, and details of the message status for that recipient
     *     - return[] struct the sending results for a single recipient
     *         - email string the email address of the recipient
     *         - status string the sending status of the recipient - either "sent", "queued", "scheduled", "rejected", or "invalid"
     *         - reject_reason string the reason for the rejection if the recipient status is "rejected" - one of "hard-bounce", "soft-bounce", "spam", "unsub", "custom", "invalid-sender", "invalid", "test-mode-limit", or "rule"
     *         - _id string the message's unique id
     */
    public function send($message, $async=false, $ip_pool=null, $send_at=null)
    {
        $_params = array("message" => $message, "async" => $async, "ip_pool" => $ip_pool, "send_at" => $send_at);
        return $this->master->call('messages/send', $_params);
    }
}