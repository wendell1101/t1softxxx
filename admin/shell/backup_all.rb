#!/usr/bin/env ruby

if Process.uid != 0
  puts "Please use superuser run this."
  exit
end

require 'json'
require 'uri'
require 'net/http'
require "fileutils"
require 'optparse'
require "date"
MYSQL_USER = "root"
MYSQL_PASSWORD = nil
START_TIME = Time.now
NOTIFICATION_CHANNEL = "backup_info"
NOTIFICATION_WEBHOOK = 'https://talk.chatchat365.com/hooks/5qmjp7o1ein5mqwaoizn4smogo'

def send_slack channel, username, message
  payload = JSON.generate({
    "channel" => channel,
    "username" => username,
    "attachments" => [{
      "text"=> message
    }],
    "icon_emoji" => ""
  })
  uri = URI(NOTIFICATION_WEBHOOK)
  res = Net::HTTP.post_form(uri, 'payload' => payload)
end

class BackupMySQL
  def initialize(backup_dir, expire_days=3)
    @backup_dir = backup_dir
    @expire_days = expire_days
    @extract_message = ""
    @prefix = "mysqldb"
    @target_dir = "#{@prefix}_#{timestamp}"
    @full_backup_dir = File.join(@backup_dir, @target_dir)
    ensure_backup_dir
    delete_old_backup
  end
  def schema
    `sudo mysql -u root -e 'show databases'`.split("\n")[1..-1].each do |database|
      database = database.strip()
      next if database == "information_schema" or database == "performance_schema"
      system "sudo mysqldump -u root --no-data #{database} > #{@full_backup_dir}/#{database}_schema.sql"
    end
  end
  def timestamp
    Time.now.strftime("%Y_%m_%d_%s")
  end
  def delete_old_backup
    puts "Delete old backup directory"
    today = Date.today
    Dir.entries(@backup_dir).each do |dir|
      /#{@prefix}_(?<year>\d\d\d\d)_(?<month>\d\d)_(?<day>\d\d)/.match(dir) do |mch|
        y = mch[:year].to_i
        m = mch[:month].to_i
        d = mch[:day].to_i
        file_date = Date.new(y, m, d)
        if (today - file_date) >= @expire_days
          expire_dir = File.join(@backup_dir, dir)
          puts "Delete expire directory #{expire_dir}"
          FileUtils.rm_r(expire_dir)
        end
      end
    end
  end
  def backup
    puts "Extracting database data"
    extract
    puts "Applying log"
    apply_log
    @full_backup_dir
  end
  def ensure_backup_dir
    if not File.directory? @backup_dir
      FileUtils.mkdir_p @backup_dir
    end
  end
  def apply_log
    @apply_log_message = `innobackupex --apply-log --export #{@full_backup_dir} 2>&1`
  end
  def password
    if MYSQL_PASSWORD
      "--password=#{MYSQL_PASSWORD}"
    else
      ""
    end
  end
  def user
    "--user=#{MYSQL_USER}"
  end
  def extract
    @extract_message = `innobackupex #{user} #{password} #{@full_backup_dir} --no-timestamp 2>&1`
  end
  def check
    puts "check status"
    flag_fail = false
    if @extract_message.include? "completed OK!"
      puts "extract successful"
    else
      puts "extract error"
      raise "Extract not complete"
    end
    if @apply_log_message.include? "completed OK!"
      puts "apply log successful"
    else
      puts "apply log error"
      raise "Apply log not complete"
    end
  end
end

def notification(msg, alert=false)
  end_time = Time.now
  hostname = `hostname`.strip
  if (msg.to_s.length > 0)
    status_msg = "#{msg}"
  else
    status_msg = "#{msg.class}"
  end
  notification_message =<<MSG
Backup MySQL database
Host: #{hostname}
Time: #{START_TIME} ~ #{end_time}
Total time: #{((end_time - START_TIME) / 60).to_i} m  #{((end_time - START_TIME) % 60).round(2)} s
Status: #{status_msg}
MSG
  if alert
    notification_message += "@channel"
  end
  send_slack NOTIFICATION_CHANNEL, hostname, notification_message
end


backup_dir = "/home/vagrant/Code/db_backup"
backup_days = 3
OptionParser.new do |opts|
  opts.banner = "Usage: backup_all.rb [-backup_dir <dir_path>]"
  opts.on("-dBKDIR", "--backup_dir=BKDIR", "backup directory default = #{backup_dir}") do |d|
    backup_dir = d
  end
  opts.on("-nDAYS", "--backup_day=DAYS", "backup expire days default = #{backup_days}") do |d|
    backup_days = d.to_i
  end
end.parse!
puts "Data backup directoy: #{backup_dir}"
begin
  restore = BackupMySQL.new backup_dir, backup_days
  backup_to_dir = restore.backup
  restore.schema
  restore.check
rescue Exception => e
  notification e, :alert => true
else
  notification "#{backup_to_dir} successful"
end
